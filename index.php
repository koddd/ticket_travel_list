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
                echo $this->xml->asXML();
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

        if(count($args) > 2) {
            $query = array_shift($args);
            // echo "<pre>".print_r($query, true)."</pre>";
            // echo "<pre>".print_r($args, true)."</pre>";

            foreach($list as $item) {
                $result = array();

                foreach($args as $arg) {
                    if(isset($item->{$arg})) {
                        $result[$arg] = (string) $item->{$arg};
                        // $result[] = settype(, "string");
                    }
                }

                array_unshift($result, $query);

                // echo "<pre>".print_r($result, true)."</pre>";
                // echo "<pre>".print_r($query, true)."</pre>";

                echo call_user_func_array('sprintf', $result);
            }
        }
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

            // echo $this->xml->asXML();
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

}

/*****************************************************************************/

/* Validation options  */

class form_validate {
    public $formerror;
    public $validation_rules;
    public $display_before;
    public $display_after;

    public function __construct() {
        $this->formerror = array();
        $this->display_before = '';
        $this->display_after = '';
        $this->validation_rules = array();
    }

    public function error_display($before = '', $after = '') {
        $this->display_before = $before;
        $this->display_after = $after;
    }

    function form_init_rules($rules = array()) {
        $this->validation_rules = $rules;
    }

    function validate() {
        $formerror = '';

        if(count($this->validation_rules)) {
            try {
                if(count($this->validation_rules) == count($_POST)) {
                    foreach($this->validation_rules as $rule) {
                        $rule = (object) $rule;
                        if(isset($_POST[$rule->name])) {
                            if(!preg_match($rule->rule, $_POST[$rule->name])) {
                                $this->formerror[$rule->name] = $rule->error;
                            }
                        }
                    }

                    if(!count($this->formerror)) {
                        return TRUE;
                    }
                } else {
                    throw new Exception("Ошибка конфига валидации, неправильное число полей формы");
                }
            } catch (Exception $e) {
                // save to log
                // display error_log
                echo $e->getMessage()." на строке ".$e->getLine();
            }
        }

        return FALSE;
    }

    function form_error($field_name = '') {
        if(count($this->formerror)) {
            if(preg_match('/\w+/', $field_name)) {
                foreach($this->formerror as $name => $error) {
                    if($name == $field_name) {
                        return $this->display_before.$error.$this->display_after;
                    }
                }
            }
        }

        return FALSE;
    }

    function form_value($field, $default = '') {
        if(isset($field)) {
            if(isset($_POST[$field]) || isset($_GET[$field])) {
                echo $_POST[$field];
            } else {
                echo $default;
            }
        }
    }

}





/**
 * Load validation class
 */

$form_validation = new form_validate();
$form_validation->error_display("<div class=\"error\">", "</div>");

$validate_rules = array(
    array(
        "name" => "date",
        "rule" => "/\d{2}-\d{2}-[1-2]\d{3}/",
        "error" => "wrong date"
    ),
    array(
        "name" => "name",
        "rule" => "/\w{3,24}/",
        "error" => "wrong name"
    ),
    array(
        "name" => "soname",
        "rule" => "/\w{3,24}/",
        "error" => "wrong soname"
    ),
    array(
        "name" => "quantity",
        "rule" => "/\d+/",
        "error" => "wrong quantity"
    )
);

$form_validation->form_init_rules($validate_rules);

// echo "<pre>".print_r($form_validation, true)."</pre>";

/**
 * Load travel list class
 */

$travellist = new travel_list();
$travellist->display_wraper("<div>", "</div>");

// echo "<pre>".print_r($travellist, true)."</pre>";


if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {

}


if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // validate form
    if($form_validation->validate()) {

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
                case "update":
                    $item_update = array(
                        "date" => $_POST['date'],
                        "name" => $_POST['name'],
                        "soname" => $_POST['soname'],
                        "quantity" => $_POST['quantity']
                    );

                    $travellist->updEntry($id, $item_update);
                    break;
                case "delete":
                    $travellist->delete($id);
                    break;
            }
        }
    } else {

    }
    //
    // ==> then
    //
} else {
    $getlist = $travellist->getall();
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

    function onSubmit(e) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.open("POST", 'http://developex.local/testing_task/');
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                xmlhttp.status,
                    xmlhttp.getAllResponseHeaders(),
                    xmlhttp.responseText
                console.log(xmlhttp.responseText);
            }
        }
        xmlhttp.send(null);
    }

</script>

<div class="monitor" id="travelmonitor">
    <?php echo $travellist->getall("<div class='result_row'><span class='date'>Дата: %s</span> <div class='name'>ФИО: %s %s</div> <div>Количество мест: => %s</div><div class='links'><a href='./?action=edit&id=%s'>изменить</a> <a href='./?action=del&id=%5\$s'>удалить</a></div></div>", "date", "name", "soname", "quantity", "id"); ?>
</div>


<h1>Добавить продукт</h1>
<form action="./index.php?action=add" method="POST" id="travelform" onsubmit="return onSubmit();">

    <div class="form_row">
        <label>Дата</label>
        <input type="text" name="date" value="<?php $form_validation->form_value('date', date('d-m-Y')); ?>" maxlength="32" class="input_text" />
        <?php $form_validation->form_error('date'); ?>
    </div>
    <div class="form_row">
        <label>Имя</label>
        <input type="text" name="name" value="<?php $form_validation->form_value('name'); ?>" maxlength="32" class="input_text" />
        <?php $form_validation->form_error('name'); ?>
    </div>
    <div class="form_row">
        <label>Фамилия</label>
        <input type="text" name="soname" value="<?php $form_validation->form_value('soname'); ?>" maxlength="32" class="input_text" />
        <?php $form_validation->form_error('soname'); ?>
    </div>
    <div class="form_row">
        <label>Количество мест</label>
        <select name="quantity">
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
        </select>
        <?php $form_validation->form_error('quantity'); ?>
    </div>
    <div class="form_row">
        <input type="submit" value="Отправить">
    </div>
</form>

</body>
</html>

