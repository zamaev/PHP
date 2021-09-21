<?php

class shopUpdaterStart
{
    public static function start()
    {

        // === добавить реф.номер в описание
        // $productParamsModel = new shopProductParamsModel();
        // foreach ((new shopProductsCollection())->getProducts("*", 0, 1844674407370955161) as $prod) {
        //     $prod['params'] = $productParamsModel->getData((new shopProduct($prod['id'])));
        //     $new_desc = $prod['description'] . ' <div style="display: none;">Ref.N: ' . $prod['params']['Ref.N'] . '</div>';

        //     $product = new shopProduct($prod['id']);
        //     wa_dumpc($product->save(array('description' => $new_desc)));

        //     wa_dump($prod);
        //     break;
        // }



        // === обновить у языков описание дополнив реферальным номером
        // $model = new shopProductParamsModel();
        // $data = $model->query("SELECT * FROM `mylang` WHERE `type`='product' AND `subtype`='description'")->fetchAll();
        // foreach ($data as $prod) {
        //     $params = $model->getData((new shopProduct($prod['type_id'])));
        //     $sql = "UPDATE `mylang` SET `text` = CONCAT(`text`, '".'<div style="display: none;"> Ref.N: '.$params['Ref.N'].' </div>'."') WHERE `type`='product' and subtype='description' and type_id={$prod['type_id']}";
        //     wa_dumpc($sql);
            // try {
            //     $model->query($sql);
            // } catch(Exception $e) {
            //     wa_dumpc('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!', $e);
            // }

            // break;
        // }


        
        // === продукты у котрых нет описанию в другом языке
        // $model = new shopProductParamsModel();
        // $lang_prod_ids = array();
        // foreach($model->query("SELECT * FROM `mylang` WHERE `type`='product' AND `subtype`='description'")->fetchAll() as $d) {
        //     $lang_prod_ids[$d['type_id']] = $d['type_id'];
        // }
        // $shop_prod_ids = array();
        // foreach ((new shopProductsCollection())->getProducts("*", 0, 1844674407370955161) as $prod) {
        //     $shop_prod_ids[$prod['id']] = $prod['id'];
        // }
        // foreach(array_diff($shop_prod_ids, $lang_prod_ids) as $prod_id) {
        //     $ref_n = $model->getData((new shopProduct($prod_id)))['Ref.N'];
        //     $sql = "INSERT INTO `mylang` (`locale`, `type`, `subtype`, `type_id`, `text`) VALUES ('ru_RU', 'product', 'description', $prod_id, '".'<div style="display: none;"> Ref.N: '.$ref_n.' </div>'."')";
        //     wa_dumpc($sql);
        //     $model->query($sql);

        //     // break;
        // }

        // wa_dump('stop script');


        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit','500M');

        $xml_url = 'https://talmarproperty.com/media/newportal.xml';
        $shopUpdaterParserXML = new shopUpdaterParserXML($xml_url);

        $features_updater = new shopUpdaterFeatures($shopUpdaterParserXML->getFeatures(), $shopUpdaterParserXML->getFeatTranslations());
        $features = $features_updater->update();

        $products_updater = new shopUpdaterProducts($shopUpdaterParserXML->getProducts(), $features);
        $products_updater->update();
    }
}
