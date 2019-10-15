<?php

class parsing
{
    public $IBLOCK_ID = 4;
    public $NPage = 1; // Количество загружаемых объектов за раз
    public $Elements = array();
    public $Pause = 20; //Пауза между обращениями к удаленному сайту (При частых выдает - Link to database cannot be established)
    private $dir = '';

    function __construct() {
        $this->dir = $_SERVER["DOCUMENT_ROOT"] . "/upload/cache_parse/";
        $this->findelement();
        foreach ($this->Elements as $el){
            sleep($this->Pause);
            echo $el['FIELDS']['ID'] . " --- " .$el['FIELDS']['NAME'] . " --- " . $el['PROPS']['OLD_SITE_LINK']['~VALUE'] . "<br>";
            $html = $this->curlload($el['PROPS']['OLD_SITE_LINK']['~VALUE']);
            if ($html && strpos($html, "Link to database cannot be established") === false){
                $this->parsingelement($html, $el['FIELDS']['ID']);
            } else {
                echo $html;
            }
        }
    }

    function findelement()
    {
        $arSelect = Array("ID", "NAME", "IBLOCK_ID");
        $arFilter = Array("IBLOCK_ID" => $this->IBLOCK_ID, "ACTIVE" => "Y", "!PROPERTY_LOADED" => "Y", "!PROPERTY_OLD_SITE_LINK" => false );
        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize" => $this->NPage), $arSelect);
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arProps = $ob->GetProperties();
            $this->Elements[] = array("FIELDS" => $arFields, "PROPS" => $arProps);
        }
    }

    function curlload($link){
        if (strpos($link, "id_product") !== false){
            $id_product = substr ($link, strpos($link, 'id_product')+11, 1000);
            $filename = $this->dir . $id_product . '.html';
            $cache_content = file_get_contents($filename);
        } else {
            $id_product = substr ($link, strpos($link, 'img/p/')+6, 1000);
            $filename = $this->dir . $id_product;
            $cache_content = file_get_contents($filename);
        }

        if ($cache_content){
            return $cache_content;
        } else {
            $strhtml = false;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt ($ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 7.1; WOW64) AppleWebKit/517.1 (KHTML, like Gecko) Chrome/21.0.1207.1 Safari/547.1');
            curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);  //Переходим по редиректам
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch,CURLOPT_ENCODING, '');
            $strhtml = curl_exec($ch);
            file_put_contents ($filename, $strhtml);
            curl_close($ch);
            return $strhtml;
        }
    }

    function parsingelement($lhtml, $id_element){

        $html = str_get_html($lhtml);

        $title = $this->de_ascii_russian($html->find('title', 0)->innertext);
        $meta_description = $html->find("meta[name='description']", 0)->content;
        $meta_keywords = $html->find("meta[name='keywords']", 0)->content;

        //////////////////////////////// pic /////////////////
        $nfile = 0;
        $previewpic = '';
        $more_photo = array();
        foreach($html->find("ul[id=thumbs_list]", 0)->children as $element) {
            $picelement = $element->find('a', 0)->href;
            $this->curlload ($picelement);
            $id_product = substr ($picelement, strpos($picelement, 'img/p/')+6, 1000);
            $filename = $this->dir . $id_product;
            if ($nfile == 0) {
                $previewpic = CFile::MakeFileArray($filename);
            } else {
                $more_photo[] = CFile::MakeFileArray($filename);
            }
            $nfile++;
        }

        $fulldescr = $html->find("#idTab1", 0)->innertext;

        ////////////////// Заполням элемент /////////////////////
        $el = new CIBlockElement;
        $arLoadProductArray = Array(
            "PREVIEW_TEXT"   => $fulldescr,
            "DETAIL_TEXT"    => $fulldescr,
            "DETAIL_PICTURE" => $previewpic,
            "PREVIEW_PICTURE" => $previewpic,
            "IPROPERTY_TEMPLATES"=>Array(
                "ELEMENT_META_TITLE" => $title,
                "ELEMENT_META_DESCRIPTION" => $meta_description,
                "ELEMENT_META_KEYWORDS" => $meta_keywords,
            )
        );
        $res = $el->Update($id_element, $arLoadProductArray);
        CIBlockElement::SetPropertyValuesEx($id_element, false,
            array(
                "MORE_PHOTO" => $more_photo,
                "LOADED" => "Y",
                )
        );
    }

    function de_ascii_russian($text) {
        return strtr($text, array(
        '&#1040;' => 'А',
        '&#1041;' => 'Б',
        '&#1042;' => 'В',
        '&#1043;' => 'Г',
        '&#1044;' => 'Д',
        '&#1045;' => 'Е',
        '&#1046;' => 'Ж',
        '&#1047;' => 'З',
        '&#1048;' => 'И',
        '&#1049;' => 'Й',
        '&#1050;' => 'К',
        '&#1051;' => 'Л',
        '&#1052;' => 'М',
        '&#1053;' => 'Н',
        '&#1054;' => 'О',
        '&#1055;' => 'П',
        '&#1056;' => 'Р',
        '&#1057;' => 'С',
        '&#1058;' => 'Т',
        '&#1059;' => 'У',
        '&#1060;' => 'Ф',
        '&#1061;' => 'Х',
        '&#1062;' => 'Ц',
        '&#1063;' => 'Ч',
        '&#1064;' => 'Ш',
        '&#1065;' => 'Щ',
        '&#1066;' => 'Ъ',
        '&#1067;' => 'Ы',
        '&#1068;' => 'Ь',
        '&#1069;' => 'Э',
        '&#1070;' => 'Ю',
        '&#1071;' => 'Я',
        '&#1072;' => 'а',
        '&#1073;' => 'б',
        '&#1074;' => 'в',
        '&#1075;' => 'г',
        '&#1076;' => 'д',
        '&#1077;' => 'е',
        '&#1078;' => 'ж',
        '&#1079;' => 'з',
        '&#1080;' => 'и',
        '&#1081;' => 'й',
        '&#1082;' => 'к',
        '&#1083;' => 'л',
        '&#1084;' => 'м',
        '&#1085;' => 'н',
        '&#1086;' => 'о',
        '&#1087;' => 'п',
        '&#1088;' => 'р',
        '&#1089;' => 'с',
        '&#1090;' => 'т',
        '&#1091;' => 'у',
        '&#1092;' => 'ф',
        '&#1093;' => 'х',
        '&#1094;' => 'ц',
        '&#1095;' => 'ч',
        '&#1096;' => 'ш',
        '&#1097;' => 'щ',
        '&#1098;' => 'ъ',
        '&#1099;' => 'ы',
        '&#1100;' => 'ь',
        '&#1101;' => 'э',
        '&#1102;' => 'ю',
        '&#1103;' => 'я'
    ));
    }
}