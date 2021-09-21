<?php

class shopUpdaterFeatures
{
    protected $feature_model;
    protected $type_features_model;

    protected $new_features = array();
    protected $new_features_translations = array();
    protected $site_features = array();

    public function __construct($new_features, $new_features_translations)
    {
        $this->feature_model = new shopFeatureModel();
        $this->type_features_model = new shopTypeFeaturesModel();
        $this->new_features = $new_features;
        $this->new_features_translations = $new_features_translations;
        $this->siteFeaturesRefresh();
    }

    public function siteFeaturesRefresh()
    {
        foreach ($this->feature_model->getFeatures(true) as $feature) {
            if ($feature['code'] == 'weight' || $feature['code'] == 'gtin') {
                continue;
            }
            $this->site_features[$feature['name']] = $feature;
        }
        $this->site_features = $this->feature_model->getValues($this->site_features);
    }

    public function update()
    {
        foreach ($this->new_features as $new_feature_key => $new_features) {
            $func = $new_feature_key . 'Update';
            $this->$func($new_features);
        }
        $this->updateTranslations();
        return $this->site_features;
    }

    public function setTypesForFeature($feature_id)
    {
        // сделать характеристики доступными для всех типов и в том числе для типа Недвижимость
        $types = array( 0 => 0, 5 => 5, );
        $this->type_features_model->updateByFeature($feature_id, $types);
    }

    public function intUpdate($new_features)
    {
        foreach ($new_features as $feature_name => $values) {
            if (!array_key_exists($feature_name, $this->site_features)) {
                $feat = array(
                    'name' => $feature_name,
                    'type' => 'double',
                    'available_for_sku' => 1,
                );
                $feat_id = $this->feature_model->save($feat);
                $this->setTypesForFeature($feat_id);
            }
        }
    }

    public function stringUpdate($new_features, $multiple = 0)
    {
        foreach ($new_features as $new_feature_name => $new_feature_values) {
            if (!array_key_exists($new_feature_name, $this->site_features)) {
                $feat = array(
                    'name' => $new_feature_name,
                    'type' => 'varchar',
                    'selectable' => 1,
                    'multiple'   => $multiple,
                    'available_for_sku' => 1,
                );
                $feat_id = $this->feature_model->save($feat);
                $this->setTypesForFeature($feat_id);
                $update_feature = $this->feature_model->getById($feat_id);
                $update_feature_values = $new_feature_values;

            } else {
                $update_feature = $this->site_features[$new_feature_name];
                $site_feature_values = $this->site_features[$new_feature_name]['values'];
                $update_feature_values = array_unique(array_merge($site_feature_values, $new_feature_values)); // объединение новых и старых занчений
            }

            // Для загрузки новых значений нужны отрицательные ключи
            $i = 1;
            $update_feature_values_clear = array();
            foreach ($update_feature_values as $ufv) {
                if ($k = array_search($ufv, $site_feature_values)) {
                    $update_feature_values_clear[$k] = $ufv;
                } else {
                    $update_feature_values_clear[$i*(-1)] = $ufv;
                    $i++;
                }
            }

            $this->feature_model->setValues($update_feature, $update_feature_values_clear);
        }
    }

    public function selectUpdate($new_features)
    {
        $this->stringUpdate($new_features, 1);
    }

    public function treeUpdate($new_features)
    {
        $this->stringUpdate($new_features, 1);
    }

    public function updateTranslations()
    {
        $this->siteFeaturesRefresh();
        wa('mylang');
        $lang_model = new mylangModel();

        $_mylang_features = $lang_model->query("SELECT * FROM `mylang` WHERE `type`='feature'")->fetchAll();
        $mylang_features = array();
        foreach ($_mylang_features as $mf) {
            $mylang_features[$mf['type_id']] = $mf;
        }
        $_mylang_features_values = $lang_model->query("SELECT * FROM `mylang` WHERE `type`='feature_value'")->fetchAll();
        $mylang_features_values = array();
        foreach ($_mylang_features_values as $mf) {
            $mylang_features_values[$mf['type_id']] = $mf;
        }

        foreach ($this->site_features as $feat) {
            if (!array_key_exists($feat['id'], $mylang_features) && isset($this->new_features_translations[$feat['name']])) {
                $feat_lang = array(
                    'locale' => 'ru_RU',
                    'type' => 'feature',
                    'subtype' => 'name',
                    'type_id' => $feat['id'],
                    'text' => $this->new_features_translations[$feat['name']],
                    'app_id' => 'shop',
                );
                $lang_model->insert($feat_lang);
            }
            foreach ($feat['values'] as $feat_value_key => $feat_value_name) {
                if (!array_key_exists($feat_value_key, $mylang_features_values) && isset($this->new_features_translations[$feat_value_name])) {
                    $feat_value_lang = array(
                        'locale' => 'ru_RU',
                        'type' => 'feature_value',
                        'subtype' => 'varchar',
                        'type_id' => $feat_value_key,
                        'text' => $this->new_features_translations[$feat_value_name],
                        'app_id' => 'shop',
                    );
                    $lang_model->insert($feat_value_lang);
                }
            }
        }
    }

}