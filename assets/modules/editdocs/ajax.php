<?php

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once(__DIR__ . "/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
$modx->invokeEvent("OnWebPageInit");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) {
    $modx->sendRedirect($modx->config['site_url']);
}
//////
if (IN_MANAGER_MODE != "true" || empty($modx) || !($modx instanceof DocumentParser)) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    header("location: " . $modx->getManagerPath() . "?a=106");
}
if (!is_array($modx->event->params)) {
    $modx->event->params = array();
}
if (!isset($_SESSION['mgrValidated'])) {
    die();
}
/////

$obj = new editDocs($modx);

if ($_POST['clear']) {
    $obj->clearCache();
    echo 'Кэш очищен';
}


if ($_POST['bigparent'] || $_POST['bigparent'] == '0') {
    echo $obj->getAllList();
}


if ($_POST['id']) {

    echo $obj->editDoc();

}

if ($_FILES['myfile']) {
    //print_r($_FILES);
    echo $obj->uploadFile();

}
if ($_POST['upd']) {
    //print_r($_FILES);    e
    echo $obj->updateExcel();

}

if ($_POST['imp']) {
    //print_r($_FILES);
    echo $obj->importExcel();

}


if ($_POST['export']) {
    //print_r($_FILES);
    echo $obj -> export();


}

/////////////// CLASS ////////////

class editDocs
{
    public function __construct($modx)
    {
        include_once(MODX_BASE_PATH . "assets/lib/MODxAPI/modResource.php");
        $this->modx = $modx;
        $this->doc = new modResource($this->modx);
    }

    public function editDoc()
    {

        $this->id = $_POST['id'];
        $this->data = $_POST['dat'];
        $this->pole = $_POST['pole'];

        $this->doc->edit($this->id);
        $this->doc->set($this->pole, $this->data);
        $this->end = $this->doc->save(false, false);

        if ($this->end) {
            return '<div class="alert-ok">Ресурс ' . $this->id . ' - отредактирован!';
        } else {
            return '<div class="alert-err">ERROR!</div>';
        }

    }


    public function getAllList()
    {

        $this->parent = $this->modx->db->escape($_POST['bigparent']);

        //return $this->parent;

        if ($_POST['fields']) {

            $this->fields = $this->modx->db->escape($_POST['fields']);
            $this->depth = $this->modx->db->escape($_POST['tree']);

            if ($_POST['paginat']) $this->disp = 20; else $this->disp = 0;
            if ($_POST['neopub']) $this->addw = 1; else $this->addw = '';



            foreach ($this->fields as $val) {
                $this->r .= '[+' . $val . '+] - ';
                $this->tvlist .= $val . ',';
                $this->rowth .= '<td>' . $val . '</td>';
                //$this->rowtd .= '<td><input type="text" name="' . $val . '" value="[+' . $val . '+]"  /></td>';
                $this->rowtd .= '<td><textarea name="' . $val . '" class="tarea">[+' . $val . '+]</textarea></td>';
            }

            $this->tvlist = substr($this->tvlist, 0, strlen($this->tvlist) - 1);
            $this->tab = '
<form id="dataf">
    <table class="tabres">
        <tr>
            <td>id</td>' . $this->rowth . '
        </tr>
        ';
            $this->endtab = '</table></form><br/>';

            $this->out = $this->modx->runSnippet('DocLister', array(
                'idType' => 'parents',
                'depth' => $this->depth,
                'parents' => $this->parent,
                'showParent' => 1,
                'id' => 'list',
                'paginate' => 'pages',
                'pageLimit' => '1',
                'pageAdjacents' => '5',
                'TplPage' => '@CODE:<span class="page">[+num+]</span>',
                'TplCurrentPage' => '@CODE:<b class="current">[+num+]</b>',
                'TplNextP' => '',
                'TplPrevP' => '',
                'TplDotsPage' => '@CODE:&nbsp;...&nbsp;',
                'display' => $this->disp,
                'tvPrefix' => '',
                //'ownerTPL' => '@CHUNK: paginateEditDocs',
                'ownerTPL' => '@CODE: [+dl.wrap+][+phx:if=`[+list.pages+]`:ne=``:then=`<tr><td colspan="100" align="center"><br/>[+list.pages+]<br/></td></tr>`+]',
                'tvList' => $this->tvlist,
                'tpl' => '@CODE:  <tr class="row"><td class="idd">[+id+]</td>' . $this->rowtd . '</tr>',
                'showNoPublish' => $this->addw

            ));

            //$this->paginate = $this->modx->getPlacholder('list.pages');

            return $this->tab . $this->out . $this->endtab;

        } else return 'Выберите поля/TV для редактирования!';
    }


    public function uploadFile()
    {

        $this->output_dir = MODX_BASE_PATH . "assets/modules/editdocs/uploads/";

        $this->ret = array();

        //	This is for custom errors;
        /*	$custom_error= array();
            $custom_error['jquery-upload-file-error']="File already exists";
            echo json_encode($custom_error);
            die();
        */
        $this->error = $_FILES["myfile"]["error"];
        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES["myfile"]["name"])) //single file
        {
            $this->fileName = $_FILES["myfile"]["name"];
            move_uploaded_file($_FILES["myfile"]["tmp_name"], $this->output_dir . $this->fileName);
            $this->ret[] = $this->fileName;
        }
        /* else  //Multiple files, file[]
         {
             $this->fileCount = count($_FILES["myfile"]["name"]);
             for($i=0; $i < $this->fileCount; $i++)
             {
                 $this->fileName = $_FILES["myfile"]["name"][$i];
                 move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$this->output_dir.$this->fileName);
                 $this->ret[]= $this->fileName;
             }

         }*/
        include_once MODX_BASE_PATH . "assets/modules/editdocs/libs/PHPExcel/IOFactory.php";
        $this->objPHPExcel = PHPExcel_IOFactory::load($this->output_dir . $this->fileName);

        //echo json_encode($this->ret);
        $this->sheetData = $this->objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $_SESSION['data'] = $this->sheetData;
        echo $this->table($this->sheetData);


        //print_r($this->sheetData);
        //print_r($this->checkField('pagetitle',$this->modx));
        //print_r($this->table($this->sheetData));
        //echo $this->table($this->sheetData).$this->zag[B];
        //print_r($this->checkField('sdsdsd',$this->modx));
        //$this->gg = $this->checkField('art',$this->modx);
        //echo array_push($this->gg, $this->gg);
        //print_r($this->newMassif($this->sheetData));


    }

    public function updateExcel()
    {
        //if($_SESSION['data']) print_r($_SESSION['data']); else return 0;
        if ($_SESSION['data']) {
            return $this->updateReady($this->newMassif($_SESSION['data'])) . $this->table($_SESSION['data']);
        } else return 'Сессия устарела, загрузите файл заново!';
    }


    public function updateReady($data)
    {
        //return 1;
        $this->data = $data;
        $this->field = $this->modx->db->escape($_POST['field']);
        $this->log = '';

        foreach ($this->data as $k => $val) {
            $this->i = 0;
            foreach ($val as $key => $value) {

                if ($key == $this->field) {
                    $this->check = $this->checkField($this->field);
                    array_push($this->check, $value);
                    $this->id = $this->getID($this->check);
                    //print_r($this->check);
                    //echo $this->id;
                }

                if ($this->id > 0) {
                    if (!isset($_POST['test'])) {
                        $this->doc->edit($this->id);
                        $this->doc->set($key, $value);
                        $this->doc->save(false, false);
                        $this->log .= 'id-' . $this->id . ';' . $key . '=>' . $value . '<br/>';
                    } else $this->log .= 'id-' . $this->id . ';' . $key . '=>' . $value . ' - Тестовый режим! <br/>';
                } elseif ($this->i < 1) $this->log .= 'Не найдено совпадений по значению - <b>' . $value . '</b>! <br/>';

                $this->i++;
            }
            $this->log .= '<hr/>';

        }
        //print_r($this->check);
        return $this->log;


    }

    public function importExcel()
    {

        if (!$_POST['parimp']) {
            return '<div class="alert-ok ">Введите ID родителя!</div>' . $this->table($_SESSION['data']);
        }
        if ($_SESSION['data']) {
            return $this->importReady($this->newMassif($_SESSION['data'])) . $this->table($_SESSION['data']);
        } else return 'Сессия устарела, загрузите файл заново!';
    }


    protected function importReady($data)
    {

        $this->data = $data;

        $this->log = '';

        foreach ($this->data as $k => $val) {
            $this->inbase = 0;
            foreach ($val as $key => $value) {

                $this->create['parent'] = $this->modx->db->escape($_POST['parimp']);
                $this->create[$key] = $value;
                if ($_POST['tpl']) $this->tpl = $this->modx->db->escape($_POST['tpl']);
                if ($this->tpl != 'file') $this->create['template'] = $this->tpl;

                if($key==$_POST['checktv']) { //проверяем если артикул в базе
                    $this->inbase = $this->checkArt($value);
                }

            }

            if (!isset($_POST['test']) && $this->inbase==0) {


                $this->doc->create($this->create);
                $this->doc->save(false, false);

                foreach ($this->create as $key => $val) {
                    $this->log .= $key . ' - ' . $val . ' -> [ok!]<br/>';
                }
            }elseif($this->inbase>0) {
                foreach ($this->create as $key => $val) {
                    if($key==$_POST['checktv']) {
                        $this->log .= $key . ' - ' . $val . ' - Уже есть в базе! НЕ ДОБАВЛЕНО! <br/>';
                    }
                }
            }
            else {
                foreach ($this->create as $key => $val) {
                    $this->log .= $key . ' - ' . $val . ' - Тестовый режим! <br/>';;
                }
            }


            $this->log .= '<hr/>';

        }


        return $this->log;


    }

    protected function newMassif($data)
    {
        $j = 0;
        $this->data = $data;
        $this->sheetDataNew = array();

        foreach ($this->data[1] as $zna) {
            $this->newkeys[$j] = $zna;
            $j++;
        }

        foreach ($this->data as $k => $val) {
            if ($k > 1) {
                $i = 0;
                foreach ($val as $key => $value) {
                    $z = $this->newkeys[$i];
                    $this->dn[$z] = $value;

                    $i++;
                }
                $this->sheetDataNew[$k] = $this->dn;
            }
        }
        unset ($this->data);
        return $this->sheetDataNew;
    }

    protected function table($data)
    {
        $this->header = '<table class="tabres">';
        $this->footer = '</table>';
        $this->zag = $data[1];
        foreach ($data as $k => $val) {

            foreach ($val as $key => $value) {

                $this->out .= '<td>' . $value . '</td>';
            }
            $this->out .= '<tr>' . $out . '</tr>';
        }
        return $this->header . $this->out . $this->footer;
    }

    protected function checkField($field)
    {
        $this->field = $field;

        $this->param = array();
        $this->res = $this->modx->db->query("SELECT name FROM " . $this->modx->getFullTableName('site_tmplvars'));
        $this->temp = 0;
        while ($this->row = $this->modx->db->getRow($this->res)) {
            if ($this->row['name'] == $this->field) {
                $this->temp = 1;
                $this->param[0] = 'tv';
                $this->param[1] = $this->field;
            }
        }
        if ($this->temp == 0) {
            $this->res = $this->modx->db->query("SHOW columns FROM " . $this->modx->getFullTableName('site_content') . " where Field = '" . $field . "'");
            if ($this->modx->db->getRecordCount($this->res) > 0) {
                $this->param[0] = 'nonetv';
                $this->param[1] = $this->field;
            } else {
                $this->param[0] = 'notfound';
                $this->param[1] = $this->field;
            }
        }
        return $this->param;

    }

    public function getID($mode)
    {

        $this->mode = $mode;
        if ($this->mode[0] == 'tv') {
            $this->res = $this->modx->db->query("SELECT contentid FROM " . $this->modx->getFullTableName('site_tmplvar_contentvalues') . " WHERE value='" . $this->mode[2] . "'");
            if ($this->modx->db->getRecordCount($this->res) > 0) {
                $this->row = $this->modx->db->getRow($this->res);
                return $this->row['contentid'];
            }
        } elseif ($this->mode[0] == 'nonetv') {
            $this->res = $this->modx->db->query("SELECT id FROM " . $this->modx->getFullTableName('site_content') . " WHERE " . $this->mode[1] . "='" . $this->mode[2] . "'");
            if ($this->modx->db->getRecordCount($this->res) > 0) {
                $this->row = $modx->db->getRow($this->res);
                return $this->row['id'];
            }
        } else return 'NO';

    }

    public function export()
    {

        $this->depth = $this->modx->db->escape($_POST['depth']);
        $this->parent = $this->modx->db->escape($_POST['stparent']);

        if ($_POST['fieldz']) {

            $this->fields = $this->modx->db->escape($_POST['fieldz']);
            foreach ($this->fields as $val) {

                $this->tvlist .= $val . ',';
                $this->ph .= '[+'.$val.'+];';
                $this->head .= $val . ';';
            }
            $this->tvlist = substr($this->tvlist, 0, strlen($this->tvlist) - 1);
            $this->ph = substr($this->ph, 0, strlen($this->ph) - 1);
            $this->head = substr($this->head, 0, strlen($this->head) - 1)."\r\n";
            $this->last = array_pop($this->fields);
            //to win1251
            if ($_POST['neopub']) $this->addw = 1; else $this->addw = '';
        }

        $this->out = $this->modx->runSnippet('DocLister', array(
            'idType' => 'parents',
            'depth' => $this->depth,
            'parents' => $this->parent,
            'showParent' => -1,
            'id' => 'list',
            'display' => 'all',
            'tvPrefix' => '',
            'orderBy' => 'id ASC',
            'tvList' => $this->tvlist,
            'tpl' => '@CODE:'.$this->ph,
            'prepare' =>  function($data, $modx){
                $data[$this->last]=$data[$this->last]."\r\n";
                return $data;
            },
            'showNoPublish' => $this->addw
        ));
        if($_POST['win']==1) {
            $this->out = iconv('UTF-8','WINDOWS-1251',$this->out);
        }
        $this->file = MODX_BASE_PATH .'assets/modules/editdocs/uploads/export.csv';
        file_put_contents($this->file,$this->head.$this->out);
        if(file_exists($this->file)) return 'Success!';
        else return 'Файла не существует!';

    }

    public function clearCache()
    {

        $this->modx->clearCache();
        include_once MODX_BASE_PATH . MGR_DIR . '/processors/cache_sync.class.processor.php';
        $this->sync = new synccache();
        $this->sync->setCachepath(MODX_BASE_PATH . "assets/cache/");
        $this->sync->setReport(false);
        $this->sync->emptyCache();

        foreach (glob(MODX_BASE_PATH . 'assets/modules/editdocs/uploads/*') as $file) {
            unlink($file);
        }

    }

    protected function checkArt($art){

        $this->art = $art;
        $this->res = $this->modx->db->query("SELECT contentid,value FROM " .$this->modx->getFullTableName('site_tmplvar_contentvalues')." WHERE  value = '".$this->art."'");
        $this->data = $this->modx->db->getRecordCount($this->res);
        return $this->data;

    }


}

?>