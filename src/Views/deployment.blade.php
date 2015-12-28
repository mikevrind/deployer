<style>
	html,body { padding: 0; margin: 0; background: #000000; }
</style>

<table width="100%" height="100%" cellpadding="0" cellspacing="0" bgcolor="#000000">
	<tr>
    	<td style="padding-top: 50px; padding-bottom: 50px;" align="center" valign="top">

			<table width="500" cellpadding="0" cellspacing="0" align="center" bgcolor="#000000">
				<tr>
					<td style="font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #FFF; line-height: 23px;" align="left">

						@foreach ( $data as $line )
							{!! nl2br($line) !!}
						@endforeach

					</td>
				</tr>
			</table>

        </td>
    </tr>
</table>