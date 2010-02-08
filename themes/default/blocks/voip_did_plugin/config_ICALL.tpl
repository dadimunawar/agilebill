<table width="100%" border="0" cellspacing="3" cellpadding="1" class="row1">
	<tr valign="top">
		<td width="35%">iCall Username</td>
		<td width="65%"> 
			<input type="text" name="voip_did_plugin_plugin_data[username]" value="{$plugin.username}" class="form_field">
		</td>
	</tr>
	<tr valign="top">
		<td width="35%">iCall API Key</td>
		<td width="65%"> 
			<input type="text" name="voip_did_plugin_plugin_data[apikey]" value="{$plugin.apikey}" class="form_field">
		</td>
	</tr>
	<tr valign="top">
		<td width="35%">Test Mode</td>
		<td width="65%">
			<select name="voip_did_plugin_plugin_data[testmode]">
				<option value="0" {if $plugin.testmode == 0}selected{/if}>Off</option>
				<option value="1" {if $plugin.testmode == 1}selected{/if}>On</option>
			</select>
		</td>
</table>
<br>
<br>
