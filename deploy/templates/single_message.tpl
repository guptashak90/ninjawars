    <dt>&lt;<a target='main' href='player.php?player_id={$message.send_from}'>{$message.from|escape}</a>&gt; </dt>
    <dd class='user-message{if $message.unread} message-unread{/if}'>{$message.message}</dd>
