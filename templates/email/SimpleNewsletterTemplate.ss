<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<% base_tag %>
		<title>$Subject</title>
		<style type="text/css">
			/* Client-specific Styles */
			#outlook a{padding:0;} /* Force Outlook to provide a "view in browser" button. */
			body{width:100% !important;} .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail to display emails at full width */
			body{-webkit-text-size-adjust:none;} /* Prevent Webkit platforms from changing default text sizes. */

			/* Reset Styles */
			body{margin:0; padding:0;}
			table td{border-collapse:collapse;}
			#backgroundTable{height:100% !important; margin:0; padding:0; width:100% !important;}

			/* Template Styles */
			body, #backgroundTable{
				background-color:#FFFFFF;
			}

			/**
			* @tab Page
			* @section email border
			* @tip Set the border for your email.
			*/
			#templateContainer{
				border: 1px solid #333333;
				background-color:#161616;
			}
			/**
			* @tab Header
			* @section header style
			* @tip Set the background color and border for your email's header area.
			* @theme header
			*/
			#templateHeader{
				border-bottom:1px solid #FFFFFF;
				color: #FFFFFF;
				font-family: "HelveticaNeueLTPro-Bd", "Helvetica Neue LT Pro Bold", "HelveticaNeueBold", "HelveticaNeue-Bold", "Helvetica Neue Bold", "Helvetica Neue LT Pro", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
			}
			#templateHeader a{
				color: #FFFFFF;
				text-decoration: none;
			}

			/**
			* @tab Header
			* @section header text
			* @tip Set the styling for your email's header text. Choose a size and color that is easy to read.
			*/
			.headerContent{
				line-height:1;
				padding:5px 20px 10px;
			}

			#templateBody{
				background-color:#FFFFFF;
			}
			/**
			* @tab Body
			* @section body text
			* @tip Set the styling for your email's main content text. Choose a size and color that is easy to read.
			* @theme main
			*/
			.bodyContent div{
				color:#000000;
				font-family:'Lucida Sans Unicode',sans-serif,Verdana,Arial;
				font-size:10pt;
				line-height:140%;
				text-align:left;
			}
			.bodyContent div a{
				color:#000000;
			}
			/**
			* @tab NewsletterFooter
			*/
			#templateFooterTag{
				background: #EDEDED;
				color: #999999;
			}
			.footerTagLine{
				padding: 10px 20px;
				font-style: italic;
			}
		</style>
	</head>
	<body style="width:100% !important; -webkit-text-size-adjust:none;margin:0; padding:0;background-color:#FFFFFF;" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<center>

		<table border="0" cellpadding="0" width="600" align="center">
			<tbody>
				
				<tr>
					<td>
						<img src="https://dl.dropboxusercontent.com/u/84613485/temp/SKODA-header.jpg" alt="Header" tabindex="0" />
						<!-- <img src="{$Up.absoluteBaseURL}themes/bootstrap/img/SKODA-header.jpg" alt="Header" tabindex="0" /> -->
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				
				<tr>
					<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">

						<div style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
							$Body
						</div>
						
						<span>Bei Fragen stehen wir Ihnen unter der unten angegebenen Telefonnummer gerne zur Verfügung.</span><br /><br />
						
						<span>Mit den besten Grüßen</span><br /><br />
						<span>Ihr ŠKODA Fahrerlebnis Team</span>
							
					</td>
				</tr>
				
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				
				
				<!-- Footer -->
				
				<tr>
					<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
						<span style="color:#4aa82d"><strong>ŠKODA Fahrerlebnisse</strong><br /></span>
						<span>Operated by lmc.communication GmbH</span>
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				<tr>
					<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
						<span>Relenbergstraße 88<br /></span>
						<span>70174 Stuttgart</span>
					</td>
				</tr>

				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>

				<tr>
					<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
						<table border="0" cellpadding="0" width="100%" style="width:600px">
							<tbody>
								<tr>
									<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;"width="10%">
										<span>Tel.:</span>
									</td>
									<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
										<span><a href="tel:+49711389500347" value="+49711389500347" target="_blank">+49 711 389 500 347</a></span>
									</td>
								</tr>
								<tr>
									<td valign="top" style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
										<span>E-Mail:</span>
									</td>
									<td valign="top" style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
										<span><a href="mailto:fahrerlebnisse@skoda-events.de" target="_blank"><span style="text-decoration:none">fahrerlebnisse@skoda-events.de</span></a></span>
									</td>
								</tr>
								<tr>
									<td valign="top" style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
										<span>Web:</span>
									</td>
									<td valign="top" style="padding:0.75pt; color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
										<span><a href="http://skoda-fahrerlebnis.de" target="_blank"><span style="text-decoration:none"> skoda-fahrerlebnis.de</span></a></span>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				
				<tr>
					<td style="padding:0.75pt">
						<img border="0" src="https://dl.dropboxusercontent.com/u/84613485/temp/logo_fe_2013.jpg" alt="logo" />
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				<tr>
					<td style="color:#000000;font-family:Verdana,Arial;font-size:10pt;line-height:140%;text-align:left;">
						<span>Geschäftsführung: Oliver Langjahr<br /></span>
						<span>Handelsregister: Amtsgericht Stuttgart HRB 23256</span>
					</td>
				</tr>
				<tr>
					<td>
						<span>&nbsp;</span>
					</td>
				</tr>
				<tr>
					<td>
						<img border="0" width="600" height="95" src="https://dl.dropboxusercontent.com/u/84613485/temp/skoda_2015_footer.jpg" alt="footer" />
					</td>
				</tr>
				
			</tbody>
		</table>
		</center>
	</body>
</html>