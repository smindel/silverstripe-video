<video $Attributes>
    <% loop Versions %>
        <source src="$Filename" type='$Type'>
    <% end_loop %>
    Your browser does not support the <code>video</code> element.
</video>