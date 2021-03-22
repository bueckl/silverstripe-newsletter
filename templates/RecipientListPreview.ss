<style>

    .cms-content-tools {
        padding: 0 !important;
        background: transparent;
        width: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 70;
        border:none;
        float: none;
        clear: left;
        position: relative;
    }
</style>

<div class="cms-content-tools" style="padding: 30px;">
<h2><strong>VORSCHAU EMPFÄNGER ($Me.Count)</strong><br></h2>

<% if $Me.Count == 0 %>
    <div class="message bad" style="font-size: 21px; line-height: 30px; color: red; font-weight: bold; ">Für diese Auswahl liegen keine Empfänger vor.</div>
<% end_if %>

    <table style="width: 100% !important; border: none;">
<% loop $Me %>
    <tr>
        <td nowrap>$Pos</td>
        <td nowrap><strong>$Email</strong></td>
        <td nowrap>$Email</td>
        <td nowrap>$FirstName</td>
        <td nowrap>$Surname</td>
        <td nowrap>I/E: $InternalExternal</td>
        <td nowrap>E: $getSubeventName</td>
        <%-- <td nowrap>E: $Hotel.NameMatch</td> --%>
    </tr>
<% end_loop %>
    </table>


</div>
