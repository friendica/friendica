<?php

namespace Friendica\Model;

use Friendica\BaseModel;

/**
 * Custom profile field model class.
 *
 * Custom profile fields are user-created arbitrary profile fields that can be assigned a permission set to restrict its
 * display to specific Friendica contacts as it requires magic authentication to work.
 *
 * @property int    uid
 * @property int    order
 * @property int    psid
 * @property string label
 * @property string value
 * @property string created
 * @property string edited
 */
class ProfileField extends BaseModel
{

}
