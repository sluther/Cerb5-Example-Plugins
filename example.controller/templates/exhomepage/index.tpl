The page content can be displayed with $page->render() inside of the main controllers index.tpl file.

<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<div id="exampleTabs">
	<ul>
		{foreach from=$tab_manifests item=tab_manifest}
			{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
				{$tabs[] = $tab_manifest->params.uri}
				<li><a href="{devblocks_url}ajax.php?c=examplecontroller&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</a></li>
			{/if}
		{/foreach}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#exampleTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>