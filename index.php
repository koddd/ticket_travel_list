<?php

class travel_list {
    public $save_path;
    public $filename;
    public $group;
    public $modifyed;
    public $xml;
    public $before;
    public $after;

    public function __construct($filename = '', $group = 'ticket') {
        $this->modifyed = false;
        $this->save_path = $_SERVER['DOCUMENT_ROOT'].'/testing_task/';
        $this->group = $group;
        $this->before = '';
        $this->after = '';

        if(strlen($filename) > 0) {
            $this->filename = $filename;
        } else {
            $this->filename = $this->save_path.'data.xml';
        }

        $this->open();
    }

    public function __destruct() {
        if($this->modifyed) {
            if($this->save()) {
                $this->modifyed = false;
            }
        }
    }

    public function open() {
        try {
            if(file_exists($this->filename)) {
                $this->xml = simplexml_load_file($this->filename);
            } else {
                $this->xml = new SimpleXMLElement("<root></root>");
                // echo $this->xml->asXML();
                // throw new Exception("Отсутствует файл ".$this->filename);
            }
        } catch (Exception $e) {
            // save to log
            // display error_log
            echo $e->getMessage()." на строке ".$e->getLine();
        }

        return FALSE;
    }

    public function save() {
        if($this->modifyed) {
            try {
                if(file_put_contents($this->filename, $this->xml->asXML(), LOCK_EX)) {
                    return true;
                } else {
                    throw new Exception("Ошибка записи в файл ".$this->filename);
                }
            } catch (Exception $e) {
                // save to log
                // display error_log
                echo $e->getMessage()." на строке ".$e->getLine();
            }
        }

        return FALSE;
    }

    public function getall() {
        $args = func_get_args();
        $list = $this->xml->xpath('//root/'.$this->group);
        $result_string = '';

        if(count($args) > 2) {
            $query = array_shift($args);

            foreach($list as $item) {
                $result = array();

                foreach($args as $arg) {
                    if(isset($item->{$arg})) {
                        $result[$arg] = (string) $item->{$arg};
                    }
                }

                array_unshift($result, $query);

                $result_string .= call_user_func_array('sprintf', $result);
            }
        }

        return $result_string;
    }

    public function getEntry($id) {
        $node = $this->xml->xpath('//'.$this->group.'[id[text()="'.$id.'"]]');
        $result = array();

        foreach($node[0] as $name=>$item) {
            $result[$name] = (string) $item;
        }

        return (object) $result;
    }

    public function getallXML() {
        $result = $this->xml->xpath('//root/'.$this->group);
        while(list( , $node) = each($result)) {
            echo $node->asXML();
        }

        // return $result->asXML();
    }

    public function display_wraper($before = '', $after = '') {
        $this->before = $before;
        $this->after = $after;
    }

    function addItem($data = array()) {
        if(count($data)) {
            $item_set = $this->xml->addChild($this->group);
            $item_set->addChild("id", uniqid());

            foreach($data as $key => $value) {
                if(!preg_match('/^(id)$/', $key)) {
                    $item_set->addChild($key, $value);
                    $this->modifyed = true;
                }
            }
        }
    }

    function updEntry($id, $upd_data) {
        $node = $this->xml->xpath('//'.$this->group.'[id[text()="'.$id.'"]]');

        foreach($upd_data as $key=>$val) {
            $node[0]->{$key} = $val;
            $this->modifyed = true;
        }
    }

    function delEntry($data) {
        if(is_array($data)) {
            $filter = "";
            foreach($data as $key => $value) {
                $filter .= "[".$key."[text()='$value']]";
            }

            $node = $this->xml->xpath("//".$this->group.$filter);

            foreach($node as $item) {
                $dom = dom_import_simplexml($item);
                if($dom->parentNode->removeChild($dom)) {
                    $this->modifyed = true;
                }
            }
        } else {
            $node = $this->xml->xpath("//".$this->group."[id[text()='$data']]");
            foreach($node as $item) {
                $dom = dom_import_simplexml($item);
                if($dom->parentNode->removeChild($dom)) {
                    $this->modifyed = true;
                }
            }
        }
    }

    function getSize() {
        if(file_exists($this->filename)) {
            return base64_encode(filesize($this->filename));
        }

        return 0;
    }
}

/**
 * Load travel list class
 */

$travellist = new travel_list();
$travellist->display_wraper("<div>", "</div>");


/*****************************************************************************/

if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if(isset($_GET['action'])) {
        switch ($_GET['action']) {
            case "get":
                $result_string = $travellist->getall("<div class='result_row'><span class='date'>Дата: %s</span> <div class='name'>ФИО: %s %s</div> <div>Количество мест: => %s</div><div class='links'><a href='./?action=edit&id=%s'>изменить</a> <a href='./?action=del&id=%5\$s'>удалить</a></div></div>", "date", "name", "soname", "quantity", "id");
                $current_size = $travellist->getSize();
                $return_string = array();

                if(isset($_GET['size'])) {
                    if($_GET['size'] == $current_size) {
                        $return_string = array(
                            "s" => 0
                        );
                    } else {
                        $return_string = array(
                            "s" => 1, // 0 => no changes, 1 => has changes
                            "size" => $current_size,
                            "result_html" => $result_string
                        );
                    }
                } else {
                    $return_string = array(
                        "s" => 1, // 0 => no changes, 1 => has changes
                        "size" => $current_size,
                        "result_html" => $result_string
                    );
                }
                echo json_encode($return_string);
                break;
            case "add":
                $item_card = array(
                    "date" => $_GET['date'],
                    "name" => $_GET['name'],
                    "soname" => $_GET['soname'],
                    "quantity" => $_GET['quantity']
                );
                $travellist->addItem($item_card);

                $return_string = array(
                    "action" => $_GET['action'],
                    "status" => "1",
                    "vars" => $item_card
                );

                echo json_encode($return_string);

                break;
            case "edit":
                if(isset($_GET['id'])) {
                    if(isset($_GET['date']) && isset($_GET['name']) && isset($_GET['soname']) && isset($_GET['quantity'])) {
                        $item_card = array(
                            "date" => $_GET['date'],
                            "name" => $_GET['name'],
                            "soname" => $_GET['soname'],
                            "quantity" => $_GET['quantity']
                        );
                        $travellist->updEntry($_GET['id'], $item_card);

                        $return_string = array(
                            "action" => $_GET['action'],
                            "id" => $_GET['id'],
                            "status" => "1",
                            "vars" => $item_card
                        );

                        echo json_encode($return_string);
                    } else {
                        $return_string = array(
                            "action" => $_GET['action'],
                            "id" => $_GET['id'],
                            "vars" => $travellist->getEntry($_GET['id'])
                        );

                        echo json_encode($return_string);
                    }
                }
                break;
            case "del":
                if(isset($_GET['id'])) {
                    $travellist->delEntry($_GET['id']);
                }
                break;
        }
    }
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    if(isset($_GET['action'])) {
        switch ($_GET['action']) {
            case "add":
                $item = array(
                    "date" => $_POST['date'],
                    "name" => $_POST['name'],
                    "soname" => $_POST['soname'],
                    "quantity" => $_POST['quantity']
                );

                $travellist->addItem($item);
                break;
            case "edit":
                $item_update = array(
                    "date" => $_POST['date'],
                    "name" => $_POST['name'],
                    "soname" => $_POST['soname'],
                    "quantity" => $_POST['quantity']
                );

                $travellist->updEntry($id, $item_update);
                break;
            case "del":
                $travellist->delete($id);
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>title tourism</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="style.css" type="text/css" media="screen, projection" rel="stylesheet">
</head>
<body>

<h1>Список покупок для путешествий</h1>

<script type="text/javascript">

    // When document is DOM ready
    var documentReady = (function(callback) {
        var document_loaded = 'load';
        if(document.loaded) {
            callback;
        } else {
            if (window.addEventListener) {
                window.addEventListener(document_loaded, callback, false);
            } else {
                window.attachEvent('on'+document_loaded, callback);
            }
        }
    });


    // Make ajax request
    function getAjaxRequest(action, params, onComplete) {
        var xmlhttp;
        try {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (E) {
                xmlhttp = false;
            }
        }
        if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
            xmlhttp = new XMLHttpRequest();
        }

        if(params.length == 0) {
            params = 'action='+action;
        }

        xmlhttp.open("GET", './?' + params);
        xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xmlhttp.onreadystatechange = function() {
            if(xmlhttp.readyState == 4) {
                if (xmlhttp.status == 200) {
                    onComplete(xmlhttp.responseText);
                }
            }
        }
        xmlhttp.send(null);
    }

    function clearVars(items) {
        for(var varname in items) {
            var element = document.getElementsByName(varname)[0];
            switch(varname) {
                case "date":
                    var today = new Date();
                    var day=today.getDate();
                    var month=today.getMonth() + 1;
                    month = month < 10 ? '0' + month : month;
                    var year=today.getFullYear();

                    element.value = day+'-'+month+'-'+year;
                    break;
                case "quantity":
                    element.options[0].selected=true;
                    break;
                default:
                    element.value = '';
                    break;
            }
        }
    }

    // On Submit button pressed
    function onSubmit(action, form_values) {
        var params = 'action=' + action + '&date=' + form_values.date.value + '&name=' + form_values.name.value + '&soname=' + form_values.soname.value + '&quantity=' + form_values.quantity.value;

        if(action == 'edit') {
            params += '&id=' + form_values.id.value;
        }

        getAjaxRequest(action, params, function(result) {
            var event = JSON.parse(result);

            switch (action) {
                case "add":
                    if(event.status == 1) {
                        clearVars(event.vars);
                        alert('Запись добавлена');
                    }
                    break;
                case "edit":
                    if(event.status == 1) {

                        // Delete old values in form fields
                        travelform.setAttribute("onsubmit", "return onSubmit('add', this);");
                        travelform.setAttribute("action", "./?action=add");
                        travelform.getElementsByTagName("h1")[0].innerHTML = "Добавить продукт";
                        document.getElementById("hidden_id").parentNode.removeChild(document.getElementById("hidden_id"));

                        clearVars(event.vars);
                        alert('Запись изменена');
                    }
                    break;
            }
        });



        return false;
    }

    // Update everything in output view container
    function update_monitor(content) {
        travelmonitor.innerHTML = content;
        // reset_anchor();
        init_anchor();
    }

    // Get updates to output screen
    function get() {
        intervalId = setInterval( function() {
            var container_size = travelmonitor.getAttribute('data-id');
            var params = 'action=get&size='+container_size;

            getAjaxRequest('get', params, function(result) {
                var event = JSON.parse(result);
                if(event.s == 1) {
                    travelmonitor.setAttribute('data-id', event.size);
                    update_monitor(event.result_html);
                }
            });
        } , 3000);
    }


    // Make a=>href onclick using function doAction
    function init_anchor() {
        var container = travelmonitor.getElementsByClassName("result_row");
        for(var i = 0; i < container.length; i++) {
            var anchor = container[i].getElementsByClassName("links")[0].getElementsByTagName("a");
            for(var k = 0; k < anchor.length; k++) {
                anchor[k].onclick = doAction;
            }
        }
    }

    // When link is clicked
    function doAction(event) {
        var params = event.target.href.match(/\/\?(.*)$/)[1];
        var params_action = event.target.href.match(/action=(\w+)&/)[1];

        getAjaxRequest(params_action, params, function(result) {
            switch (params_action) {
                case "edit":
                    var event = JSON.parse(result);

                    travelform.setAttribute("onsubmit", "return onSubmit('"+event.action+"', this);");
                    travelform.setAttribute("action", "./?action="+event.action+"&id="+event.id);
                    travelform.getElementsByTagName("h1")[0].innerHTML = "Изменить продукт"; // Добавить продукт

                    for(var varname in event.vars) {
                        if(varname == 'id') {
                            if(document.getElementById('hidden_id')) {
                                document.getElementById('hidden_id').setAttribute('value', event.vars[varname]);
                            } else {
                                var newInput = document.createElement('input');
                                newInput.setAttribute('type', 'hidden');
                                newInput.setAttribute('name', 'id');
                                newInput.setAttribute('id', 'hidden_id');
                                newInput.setAttribute('value', event.vars[varname]);
                                travelform.appendChild(newInput);
                            }
                        } else {
                            var element = document.getElementsByName(varname)[0];

                            if(element.nodeName.match(/select/i)) {
                                for(var i = 0; i < element.options.length; i++) {
                                    if(element.options[i].value == event.vars[varname]) {
                                        element.options[i].selected = true;
                                    }
                                }
                            } else {
                                element.value = event.vars[varname];
                            }
                        }
                    }
                    break;
            }
        });

        return false;
    }

    documentReady(function() {
        var ajaxStart = (function(block) {
            var travelmonitor = document.getElementById('travelmonitor');
            var travelform = document.forms["travelform"];

            init_anchor();
            get();
        });

        ajaxStart();
    });

</script>

<div class="monitor" id="travelmonitor" data-id="<?php echo $travellist->getSize(); ?>"><?php echo $travellist->getall("<div class='result_row'><span class='date'>Дата: %s</span> <div class='name'>ФИО: %s %s</div> <div>Количество мест: => %s</div><div class='links'><a href='./?action=edit&id=%s'>изменить</a> <a href='./?action=del&id=%5\$s'>удалить</a></div></div>", "date", "name", "soname", "quantity", "id"); ?></div>



<form action="./?action=add" method="POST" id="travelform" onsubmit="return onSubmit('add', this);">
    <h1>Добавить продукт</h1>

    <div class="form_row">
        <label>Дата</label>
        <input type="text" name="date" value="<?php echo date('d-m-Y'); ?>" maxlength="32" class="input_text" />
    </div>
    <div class="form_row">
        <label>Имя</label>
        <input type="text" name="name" value="" maxlength="32" class="input_text" />
    </div>
    <div class="form_row">
        <label>Фамилия</label>
        <input type="text" name="soname" value="" maxlength="32" class="input_text" />
    </div>
    <div class="form_row">
        <label>Количество мест</label>
        <select name="quantity">
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
        </select>
    </div>
    <div class="form_row">
        <input type="submit" value="Отправить">
    </div>
</form>

</body>
</html>

