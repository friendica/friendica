# Table post-media-exif

Exif data for attached media, see https://exiv2.org/tags.html

## Fields

| Field             | Description                                                                                                         | Type              | Null | Key | Default | Extra |
| ----------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------- | ---- | --- | ------- | ----- |
| media-id          | If of the post-media entry with EXIF data                                                                           | int unsigned      | NO   | PRI | NULL    |       |
| uri-id            | Id of the item-uri table entry that contains the item uri                                                           | int unsigned      | NO   |     | NULL    |       |
| raw-data          | JSON array with the raw exif data                                                                                   | text              | YES  |     | NULL    |       |
| coord             | GPS coordinates (latitude and longitude) representing the location where the picture was taken.                     | varchar(255)      | YES  |     | NULL    |       |
| FocalLength       | The focal length of the lens in mm.                                                                                 | varchar(16)       | YES  |     | NULL    |       |
| ExposureTime      | The exposure time in fractions of 1/x or full seconds.                                                              | varchar(16)       | YES  |     | NULL    |       |
| ApertureFNumber   | The lens aperture calculated as f number                                                                            | varchar(16)       | YES  |     | NULL    |       |
| ISOSpeedRatings   | The ISO speed used to expose the image.                                                                             | smallint unsigned | YES  |     | NULL    |       |
| LensSpecification | Lens specifications, for example 35mm f/2.8 or 70-200mm f/2.8-6.3                                                   | varchar(32)       | YES  |     | NULL    |       |
| FocusDistance     | The distance to the subject, given in meters.                                                                       | varchar(16)       | YES  |     | NULL    |       |
| CCDWidth          |                                                                                                                     | varchar(16)       | YES  |     | NULL    |       |
| BodySerialNumber  | The serial number of the body of the camera.                                                                        | varchar(255)      | YES  |     | NULL    |       |
| Artist            | The name of the camera owner, photographer or image creator.                                                        | varchar(255)      | YES  |     | NULL    |       |
| Copyright         | Copyright information.  In this standard the tag is used to indicate both the photographer and editor copyrights.   | varchar(255)      | YES  |     | NULL    |       |
| DateTime          | The date and time of image creation. In Exif standard, it is the time the file was changed.                         | datetime          | YES  |     | NULL    |       |
| DateTimeOriginal  | The date and time when the original image data was generated.                                                       | datetime          | YES  |     | NULL    |       |
| DateTimeDigitized | The date and time when the image was stored as digital data.                                                        | datetime          | YES  |     | NULL    |       |
| ExpandFilm        | The type or brand of film used for the image, such as analog film types (e.g., Kodak E100SW).                       | varchar(255)      | YES  |     | NULL    |       |
| ExpandLens        | The lens model or description used for the image (e.g., Nikkor 20-35mm f/2.8 zoom).                                 | varchar(255)      | YES  |     | NULL    |       |
| HostComputer      | Information about the host computer used to generate the image.                                                     | varchar(255)      | YES  |     | NULL    |       |
| ImageDescription  | A character string giving the title of the image.                                                                   | text              | YES  |     | NULL    |       |
| ImageUniqueID     | A unique identifier for each image, typically in the form of a UUID or other unique string.                         | varchar(255)      | YES  |     | NULL    |       |
| LensMake          | The name of the lens manufacturer.                                                                                  | varchar(255)      | YES  |     | NULL    |       |
| LensModel         | The model name or model number of the lens used.                                                                    | varchar(255)      | YES  |     | NULL    |       |
| Make              | The manufacturer of the recording equipment.                                                                        | varchar(255)      | YES  |     | NULL    |       |
| MakerNote         | A tag for manufacturers of Exif writers to record any desired information. The contents are up to the manufacturer. | varchar(255)      | YES  |     | NULL    |       |
| Model             | The model name or model number of the equipment.                                                                    | varchar(255)      | YES  |     | NULL    |       |
| OwnerName         | The owner of the camera.                                                                                            | varchar(255)      | YES  |     | NULL    |       |
| Orientation       | The image orientation in terms of rows and columns.                                                                 | tinyint unsigned  | YES  |     | NULL    |       |
| Software          | The name and version of the software or firmware of the camera or image input device used to generate the image.    | varchar(255)      | YES  |     | NULL    |       |
| UserComment       | A comment provided by the user about the image.                                                                     | text              | YES  |     | NULL    |       |

## Indexes

| Name    | Fields   |
| ------- | -------- |
| PRIMARY | media-id |
| uri-id  | uri-id   |

## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| media-id | [post-media](help/spec/database/db-post-media) | id |
| uri-id | [item-uri](help/spec/database/db-item-uri) | id |

Return to [database documentation](help/spec/database/index)
