<?php

class shopUpdaterProducts
{
    protected $site_features = array();
    protected $new_products = array();

    private $save_products_ids = array();
    private $delete_products_ids = array();


    public function __construct($new_products, $features)
    {
        $this->site_features = $features;
        $this->new_products = $new_products;
    }

    public function update()
    {
        $productParamsModel = new shopProductParamsModel();
        $products = array();
        foreach ((new shopProductsCollection())->getProducts("*", 0, 1844674407370955161) as $prod) {
            $prod['params'] = $productParamsModel->getData((new shopProduct($prod['id'])));
            if (isset($prod['params']['partner_id'])) {
                $products[$prod['params']['partner_id']] = $prod;
            }
        }

        $products_keys = array_keys($products);
        $new_products_keys = array_keys($this->new_products);
        $diff = array_intersect($products_keys, $new_products_keys);
        $this->save_products_ids = array_diff($new_products_keys, $diff);
        $this->delete_products_ids = array_diff($products_keys, $diff);




        // === удаление дублей
        // $test_duple = array();
        // $test_products = array();
        // foreach ((new shopProductsCollection())->getProducts("*", 0, 1844674407370955161) as $prod) {
        //     $prod['params'] = $productParamsModel->getData((new shopProduct($prod['id'])));
        //     if (isset($prod['params']['partner_id'])) {
        //         // wa_dumpc($prod['params']['partner_id'], $prod['name']);
        //         if (isset($test_products[$prod['params']['partner_id']])) {
        //             $test_duple[] = array(
        //                 'partner_id' => $prod['params']['partner_id'],
        //                 'name' =>$prod['name'] 
        //             );
        //             $this->delete_products_ids[] = $prod['params']['partner_id'];
        //         }
        //         $test_products[$prod['params']['partner_id']] = $prod;
        //     }
        // }
        // wa_dumpc($this->delete_products_ids);



        // wa_dump($this->save_products_ids);

        wa_dumpc('deleted', $this->delete_products_ids);
        wa_dumpc('saved', $this->save_products_ids);

        $this->deleteNotAvailable($products);
        // wa_dump('stop reserve');
        $this->save();
    }

    public function deleteNotAvailable($site_products)
    {
        $delete_site_ids = array();
        foreach ($site_products as $partner_id => $prod) {
            if (in_array($partner_id, $this->delete_products_ids)) {
                $delete_site_ids[] = $prod['id'];
            }
        }
        if (!empty($delete_site_ids)) {
            (new shopProductModel)->delete($delete_site_ids);
        }
    }

    public function save()
    {
        $save_count = count($this->save_products_ids);
        $i = 0;

        foreach ($this->new_products as $partner_id => $new_product) {
            if (!in_array($partner_id, $this->save_products_ids)) {
                continue;
            }

            $i++;
            $progress = (int)(($i / $save_count)*100);
            $log = $new_product['name_en'] . "\n" . $i . " / " . $save_count;

            $data = array(
                'name' => $new_product['name_en'],
                'status' => '1',
                'categories' => array(
                    ($new_product['category'] == 'sale' ? 1 : 2)
                ),
                'type_id' => 5,
                'url' => preg_replace('/[^a-zA-Z0-9_]+/', '-', trim(strtolower(waLocale::transliterate($new_product['name_en'])))),
                'description' => $new_product['description_en'] . ' <div style="display: none;"> Ref.N: ' . $new_product['Ref.N'] . ' </div>',
                'meta_title' => $new_product['name_en'],
                'meta_description' => $new_product['description_en'],
                'params' => array(
                    'partner_id' => $partner_id,
                    'url' => $new_product['url'],
                    'Ref.N' => $new_product['Ref.N'],
                    'coord_lat' => $new_product['coord']['lat'],
                    'coord_lng' => $new_product['coord']['lng'],
                ),
                'skus' => array(
                    '-1' => array(
                        'available' => '1',
                        'status' => '1',
                        'price' => $new_product['price'],
                    ),
                ),
            );

            $product = new shopProduct();
            $product->save($data);
            $this->setLang($product->id, $new_product);
            $this->setFeatures($product, $new_product['features']['int']+$new_product['features']['string']+$new_product['features']['select']+$new_product['features']['tree']);
            $this->setImages($product->id, $new_product['pictures']);
        }
    }

    private function setImages($product_id, $images)
    {
        $product_images_model = new shopProductImagesModel();

        foreach ($images as $image_url) {

            // после обработки изображения, файл удаляется
            $path = wa()->getDataPath('partner_files/'.basename($image_url), true, 'shop');
            waFiles::upload($image_url, $path);
            // wa_dump($image_url, $path);

            $file = new waRequestFile(array(
                'name'     => basename($image_url),
                'type'     => 'image/jpeg',
                'tmp_name' => $path,
                'size'     => filesize($path),
                'error'    => 0,
            ), true);

            $data = $product_images_model->addImage($file, $product_id);
            $config = wa()->getConfig();
            shopImage::generateThumbs($data, $config->getImageSizes());
            $result= array(
                'id'             => $data['id'],
                'name'           => $data['filename'],
                'size'           => $data['size'],
                'url_thumb'      => shopImage::getUrl($data, $config->getImageSize('thumb')),
                'url_crop'       => shopImage::getUrl($data, $config->getImageSize('crop')),
                'url_crop_small' => shopImage::getUrl($data, $config->getImageSize('crop_small')),
                'description'    => '',
            );
        }
    }

    private function setFeatures(shopProduct $product, $new_features)
    {
        $shopProductFeaturesModel = new shopProductFeaturesModel();
        $update_features = array();
        foreach ($new_features as $key => $feat) {
            if (is_array($feat)) {
                array_unshift($feat, '');
            }
            $update_features[$this->site_features[$key]['code']] = $feat;
        }
        $shopProductFeaturesModel->setData($product, $update_features);
    }

    private function setLang($site_prod_id, $new_prod_data)
    {
        wa('mylang');
        $lang_model = new mylangModel();

        $name_ru = array(
            'locale' => 'ru_RU',
            'type' => 'product',
            'subtype' => 'name',
            'type_id' => $site_prod_id,
            'text' => $new_prod_data['name_ru'],
            'app_id' => 'shop',
        );
        $title_ru = array(
            'locale' => 'ru_RU',
            'type' => 'product',
            'subtype' => 'meta_title',
            'type_id' => $site_prod_id,
            'text' => $new_prod_data['name_ru'],
            'app_id' => 'shop',
        );
        $description_ru = array(
            'locale' => 'ru_RU',
            'type' => 'product',
            'subtype' => 'description',
            'type_id' => $site_prod_id,
            'text' => $new_prod_data['description_ru'] . ' <div style="display: none;"> Ref.N: '.$new_prod_data['Ref.N'].' </div>',
            'app_id' => 'shop',
        );
        $meta_description_ru = array(
            'locale' => 'ru_RU',
            'type' => 'product',
            'subtype' => 'meta_description',
            'type_id' => $site_prod_id,
            'text' => $new_prod_data['description_ru'],
            'app_id' => 'shop',
        );
        $lang_model->insert($name_ru);
        $lang_model->insert($title_ru);
        $lang_model->insert($description_ru);
        $lang_model->insert($meta_description_ru);
    }

}