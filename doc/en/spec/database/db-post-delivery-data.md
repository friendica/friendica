# Table post-delivery-data

Delivery data for items

## Fields

| Field        | Description                                                                                                                                                | Type         | Null | Key | Default | Extra |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| uri-id       | Id of the item-uri table entry that contains the item uri                                                                                                  | int unsigned | NO   | PRI | NULL    |       |
| postopts     | External post connectors add their network name to this comma-separated string to identify that they should be delivered to these networks during delivery | text         | YES  |     | NULL    |       |
| inform       | Additional receivers of the linked item                                                                                                                    | mediumtext   | YES  |     | NULL    |       |
| queue_count  | Initial number of delivery recipients, used as item.delivery_queue_count                                                                                   | mediumint    | NO   |     | 0       |       |
| queue_done   | Number of successful deliveries, used as item.delivery_queue_done                                                                                          | mediumint    | NO   |     | 0       |       |
| queue_failed | Number of unsuccessful deliveries, used as item.delivery_queue_failed                                                                                      | mediumint    | NO   |     | 0       |       |
| activitypub  | Number of successful deliveries via ActivityPub                                                                                                            | mediumint    | NO   |     | 0       |       |
| dfrn         | Number of successful deliveries via DFRN                                                                                                                   | mediumint    | NO   |     | 0       |       |
| legacy_dfrn  | Number of successful deliveries via legacy DFRN                                                                                                            | mediumint    | NO   |     | 0       |       |
| diaspora     | Number of successful deliveries via Diaspora                                                                                                               | mediumint    | NO   |     | 0       |       |

## Indexes

| Name    | Fields |
| ------- | ------ |
| PRIMARY | uri-id |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
