# silverstripe-video
Adds a video as a file type, creates mp4, webm and ogv versions and a poster of the video through ffmpeg

## Features

- Adds a **new video file type** to Silverstripe like e.g. Image

  `
  class MyDataObject
  {
    private static $has_one('Video' => 'Video');
  }
  `

- Creates versions of the video in **different formats suitable to use with the HTML video tag**, namely mp4, webm and ogv
- Creates a **poster image** as an Image object so you can use all the image methods for resizing, padding and cropping
- Determins and stores the **duration** of the video
- Uses a replacable video backend to create the versions and posters, currently there is only a **FFMPEG** backend
- Lets you playback the video in the backend
- Supplys methods to customise the display in the template

  `
  <% if $Video %>
    $Video.setSize(480, 360).controls.autoplay
  <% end_if %>
  `
