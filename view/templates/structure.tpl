{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

# Table {{$name nofilter}}

{{$comment nofilter}}

## Fields

{{foreach $fields as $field}}
| {{$field.name nofilter}} | {{$field.comment nofilter}} | {{$field.type nofilter}} | {{$field.null nofilter}} | {{$field.primary nofilter}} | {{$field.default nofilter}} | {{$field.extra nofilter}} |
{{/foreach}}

## Indexes

{{foreach $indexes as $index}}
| {{$index.name nofilter}} | {{$index.fields nofilter}} |
{{/foreach}}

{{if $foreign}}
## Foreign keys

| Field | Target Table | Target Field |
|-------|--------------|--------------|
{{foreach $foreign as $key}}
| {{$key.field nofilter}} | [{{$key.targettable nofilter}}](help/spec/database/db-{{$key.targettable nofilter}}) | {{$key.targetfield nofilter}} |
{{/foreach}}
{{/if}}

Return to [database documentation](help/spec/database/index)
