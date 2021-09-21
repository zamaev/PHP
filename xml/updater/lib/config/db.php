<?php

return array(
    'shop_updater_sync_ids' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'partner_product_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'own_product_id' => array('int', 11, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'INDEX' => 'partner_product_id',
        )
    ),
);

