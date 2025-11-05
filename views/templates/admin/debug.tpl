<div class="panel" id="sjboverrides-info">
    <div class="panel-heading"><i class="icon-cogs"></i>Debug</div>
    <pre>
    {foreach from=$DATA item=item key=key name=name}
        <strong style="color:#f90"> {$key}</strong> : {$item}
    {/foreach}
    </pre>
</div>