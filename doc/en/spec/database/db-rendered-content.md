# Table rendered-content

Rendered HTML for content that is requested via the API

## Fields

| Field       | Description                                                                                      | Type             | Null | Key | Default    | Extra |
| ----------- | ------------------------------------------------------------------------------------------------ | ---------------- | ---- | --- | ---------- | ----- |
| uri-id      | Id of the item-uri table entry that contains the item or contact uri                             | int unsigned     | NO   | PRI | NULL       |       |
| mode        | Simple HTML mode of the rendering, see BBCode::EXTERNAL and BBCode::MASTODON_API                 | tinyint unsigned | NO   | PRI | NULL       |       |
| lang        | Language of the translated texts in the html, empty if the html doesn't contain translated texts | varchar(10)      | NO   | PRI |            |       |
| hash        | Hash of the rendered text and the BBCode version                                                 | varchar(32)      | NO   |     |            |       |
| html        | Text converted to html                                                                           | mediumtext       | YES  |     | NULL       |       |
| last-access | Day of the last access                                                                           | date             | NO   |     | 0001-01-01 |       |

## Indexes

| Name        | Fields             |
| ----------- | ------------------ |
| PRIMARY     | uri-id, mode, lang |
| last-access | last-access        |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
