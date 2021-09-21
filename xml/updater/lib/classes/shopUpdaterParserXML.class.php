<?php

class shopUpdaterParserXML
{
    private $xml_url = '';
    private $products = array();
    private $feat_translations = array();
    private $features = array(
        'int' => array(),
        'string' => array(),
        'select' => array(),
        'tree' => array(),
    );

    public function __construct($xml_url)
    {
        $this->xml_url = $xml_url;
        $this->parse();
    }

    public function getProducts() { return $this->products; }

    public function getFeatTranslations() { return $this->feat_translations; }

    public function getFeatures() { return $this->features; }

    public function parse()
    {
        $xml_str = file_get_contents($this->xml_url);
        $data = new SimpleXMLElement($xml_str);

        foreach ($data->ad as $ad) {
            $this->products[(int)$ad->attributes()->id] = array(
                'category' => (string)$ad->attributes()->category,
                'name_ru' => (string)$ad->name->ru,
                'name_en' => (string)$ad->name->en,
                'description_ru' => (string)$ad->description->ru,
                'description_en' => (string)$ad->description->en,
                'price' => (int)$ad->price,
                'coord' => array(
                    'lat' => (string)$ad->lat,
                    'lng' => (string)$ad->lng,
                ),
                'Ref.N' => (string)$ad->vendorCode,
                'url' => (string)$ad->url,
                'pictures' => (array)$ad->pictures->picture,
                'features' => array(
                    'int' => array(),
                    'string' => array(),
                    'select' => array(),
                    'tree' => array(),
                ),
            );
            foreach ($ad->params->param as $feat) {
                $func = $feat->attributes()->type . 'Feat';
                $this->products[(int)$ad->attributes()->id]['features'][(string)$feat->attributes()->type][(string)$feat->attributes()->name_en] = $this->$func($feat);
            }
        }
    }

    public function intFeat(SimpleXMLElement $feat)
    {
        $this->feat_translations[(string)trim($feat->attributes()->name_en)] = (string)trim($feat->attributes()->name_ru);
        if (!in_array((int)$feat->value, $this->features['int'][(string)$feat->attributes()->name_en])) {
            $this->features['int'][(string)$feat->attributes()->name_en][] = (int)$feat->value;
        }
        return (int)$feat->value;
    }

    public function stringFeat(SimpleXMLElement $feat)
    {
        $this->feat_translations[(string)trim($feat->attributes()->name_en)] = (string)trim($feat->attributes()->name_ru);
        $this->feat_translations[(string)trim($feat->value->attributes()->name_en)] = (string)trim($feat->value->attributes()->name_ru);
        if (!in_array((string)$feat->value->attributes()->name_en, $this->features['string'][(string)$feat->attributes()->name_en])) {
            $this->features['string'][(string)$feat->attributes()->name_en][] = (string)$feat->value->attributes()->name_en;
        }
        return (string)$feat->value->attributes()->name_en;
    }

    public function selectFeat(SimpleXMLElement $feat)
    {
        $this->feat_translations[(string)trim($feat->attributes()->name_en)] = (string)trim($feat->attributes()->name_ru);
        $select = array();
        foreach ($feat->value as $value) {
            $this->feat_translations[(string)trim($value->attributes()->name_en)] = (string)trim($value->attributes()->name_ru);
            if (!in_array((string)$value->attributes()->name_en, $this->features['select'][(string)$feat->attributes()->name_en])) {
                $this->features['select'][(string)$feat->attributes()->name_en][] = (string)$value->attributes()->name_en;
            }
            $select[] = (string)$value->attributes()->name_en;
        }
        return $select;
    }

    public function treeFeat(SimpleXMLElement $feat)
    {
        $this->feat_translations[(string)$feat->attributes()->name_en] = (string)$feat->attributes()->name_ru;
        $tree = array();
        foreach ($feat->option as $option) {
            $this->feat_translations[(string)$option->attributes()->name_en.": ".$option->value] = $option->attributes()->name_ru.": ".$option->value;
            if (!in_array($option->attributes()->name_en.": ".$option->value, $this->features['tree'][(string)$feat->attributes()->name_en])) {
                $this->features['tree'][(string)$feat->attributes()->name_en][] = $option->attributes()->name_en.": ".$option->value;
            }
            $tree[] = $option->attributes()->name_en.": ".$option->value;
        }
        return $tree;
    }

}