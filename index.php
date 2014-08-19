<?php

class travel_list {
    public $save_path;
    public $filename;
    public $group;
    public $modified;
    public $xml;
    public $formerror;

    public function __construct($filename = '', $group = 'ticket') {
        $this->modified = false;
        $this->save_path = './';
        $this->group = $group;
        $this->formerror = array();

        if(strlen($filename) > 0) {
            $this->filename = $filename;
        } else {
            $this->filename = $this->save_path.'data.xml';
        }

        $this->open();
    }

    public function __destruct() {
        if($this->modified) {
            if($this->save()) {
                $this->modified = false;
                // done
            }
        }

        unset($this->form_error);
    }

    public function init() {

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
        try {
            if(file_put_contents($this->filename, $this->xml->asXML())) {
                return true;
            } else {
                throw new Exception("Ошибка записи в файл ".$this->filename);
            }
        } catch (Exception $e) {
            // save to log
            // display error_log
            echo $e->getMessage()." на строке ".$e->getLine();
        }

        return FALSE;
    }

    public function getall($string = '') {
        $list = $this->xml->xpath('//root/ticket');
        $result = '';

        foreach($list as $item) {
            if(preg_match('/\w+/', $string)) {
                $result .= sprintf($string, $item->value);
            } else {
                $result .= $item->value;
            }
        }
    }

    function addItem($data = array()) {
        if(count($data)) {
            $item_set = $this->xml->root->addChild($this->group);

            foreach($data as $key => $value) {
                $item_set->addChild($key, $value);
                $this->modifyed = true;
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

}

/*****************************************************************************/

/* Validation options  */

class form_validate {
    public $formerror;
    public $display_before;
    public $display_after;

    public function __construct() {
        $this->formerror = array();
        $this->display_before = '';
        $this->display_after = '';
    }

    public function error_display($before = 'text', $after = 'text1') {
        $this->display_before = $before;
        $this->display_after = $after;
    }

    function form_validate($rules = array()) {
        $formerror = '';

        if(count($rules)) {
            try {
                if(count($rules) == count($_POST)) {
                    foreach($rules as $rule) {
                        $rule = (object) $rule;
                        if(isset($_POST[$rule->name])) {
                            if(!preg_match($rule->rule, $_POST[$rule->name])) {
                                $formerror[$rule->name] = $rule->error;
                            }
                        }
                    }

                    return $formerror;
                } else {
                    throw new Exception("Ошибка конфига валидации, неправильное число полей формы");
                }
            } catch (Exception $e) {
                // save to log
                // display error_log
                echo $e->getMessage()." на строке ".$e->getLine();
            }
        }
    }

    function form_error($field_name = '') {
        if(isset($formerror) && count($formerror)) {
            if(preg_match('/\w+/', $field_name)) {
                foreach($formerror as $name => $error) {
                    if($name == $field_name) {
                        return $error;
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

$form_validation->validate();

echo "<pre>".print_r($form_validation, true)."</pre>";

$travellist = new travel_list();
$getlist = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // validate form
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
    // $this->validate($validate_rules);
    //
    // ==> then
    //

    if(isset($_GET['action'])) {
        switch ($action) {
            case "add":

                $item = array(
                    "date" => $_POST['date'],
                    "date" => $_POST['date'],
                );

                $travellist->addItem();
                break;
            case "update":
                $travellist->update();
                break;
            case "delete":
                $travellist->delete();
                break;
        }
    }

    if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {

    }
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

<h1>Список продуктов</h1>

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
    <?php echo $getlist; ?>
</div>


<h1>Добавить продукт</h1>
<form action="./index.php?action=add" method="POST" id="travelform" onsubmit="return onSubmit();">

    <div class="form_row">
        <label>Дата</label>
        <input type="text" name="date" value="<?php form_value('date', date('d-m-Y')); ?>" maxlength="32" class="input_text" />
        <?php form_error('date'); ?>
    </div>
    <div class="form_row">
        <label>Имя</label>
        <input type="text" name="name" value="" maxlength="32" class="input_text" />
        <?php form_error('name'); ?>
    </div>
    <div class="form_row">
        <label>Фамилия</label>
        <input type="text" name="soname" value="" maxlength="32" class="input_text" />
        <?php form_error('soname'); ?>
    </div>
    <div class="form_row">
        <label>Количество мест</label>
        <select name="quantity">
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
        </select>
        <?php form_error('quantity'); ?>
        <span class="help-block">Example block-level help text here.</span>
    </div>
    <div class="form_row">
        <input type="submit" value="Отправить">
    </div>
</form>

</body>
</html>

