<XML>
  <post>
    <conversation>
      <guid>{{$conv.guid}}</guid>
      <subject>{{$conv.subject}}</subject>
      <created_at>{{$conv.created_at}}</created_at>

      {{foreach $conv.messages as $msg}}

      <message>
        <guid>{{$msg.guid}}</guid>
        <parent_guid>{{$msg.parent_guid}}</parent_guid>
        {{if $msg.parent_author_signature}}
        <parent_author_signature>{{$msg.parent_author_signature}}</parent_author_signature>
        {{/if}}
        <author_signature>{{$msg.author_signature}}</author_signature>
        <text>{{$msg.text}}</text>
        <created_at>{{$msg.created_at}}</created_at>
        <diaspora_handle>{{$msg.diaspora_handle}}</diaspora_handle>
        <conversation_guid>{{$msg.conversation_guid}}</conversation_guid>
      </message>

      {{/foreach}}

      <diaspora_handle>{{$conv.diaspora_handle}}</diaspora_handle>
      <participant_handles>{{$conv.participant_handles}}</participant_handles>
    </conversation>
  </post>
</XML>
