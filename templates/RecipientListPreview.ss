<div style="padding: 30px;">
<h2><strong>VORSCHAU EMPFÄNGER</strong><br></h2>

<% if $Me.Count == 0 %>
    <div class="message bad" style="font-size: 21px; line-height: 30px; color: red; font-weight: bold; ">Für diese Auswahl liegen keine Empfänger vor.</div>
<% end_if %>


<div style="-webkit-column-count: 2; -moz-column-count: 2; column-count: 2; margin-bottom: 40px;">
<% loop $Me %>
    <div style="font-size: 13px; border-bottom: 1px solid #fff;">($Pos) $FirstName, $Surname ($Locale) => $Email</div>

<% end_loop %>
</div>


</div>
