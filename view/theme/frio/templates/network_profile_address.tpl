{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
						<div>
							{{if $item.network_svg && $item.plink}}
								<span class="wall-item-network"><a href="{{$item.plink.href}}" class="plink u-url" target="_blank"><img class="network-svg" src="{{$item.network_svg}}" alt="{{$item.network_name}} - {{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" loading="lazy"/></a></span>
							{{elseif $item.plink}}
									<a href="{{$item.plink.href}}" class="plink u-url" aria-label="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" target="_blank">{{$item.network_name}}</a>
							{{elseif $item.network_svg}}
								<span class="wall-item-network"><img class="network-svg" src="{{$item.network_svg}}" title="{{$item.network_name}}" loading="lazy" aria-hidden="true"/></span>
							{{else}}
									<span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span>
							{{/if}}
							<span class="wall-item-addr text-muted">@{{$item.profile_addr}}</span>
						</div>
