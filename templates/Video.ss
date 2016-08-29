<video $Attributes>
    <% loop Versions %>
        <source src="$Filename" type='$Type'>
    <% end_loop %>
    <%t Video.TAG_MESSAGE "Your browser does not support the <code>video</code> element." %>
</video>