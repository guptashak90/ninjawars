{if $pages gt 1}
<div class="message-nav">
	{if $current_page > 1}
  <a href="messages.php?page={math equation="x-1" x=$current_page}">Prev</a>
	{else}
  Prev
	{/if}
  - {$current_page} / {$pages} -
	{if $current_page < $pages}
  <a href="messages.php?page={math equation="x+1" x=$current_page}">Next</a>
	{else}
  Next
	{/if}
</div>
{/if}