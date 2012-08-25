---
layout: post
date:  2012-08-25 15:12:20-06:00
title: Letterboxing with FFmpeg/Avconv for Mobile
description: "How to box (letterbox or pillarbox) video for mobile devices \
using ffmpeg or avconv."
tags: [ howto ]
---

Although the [ffmpeg](http://ffmpeg.org/ffmpeg.html) (and
[avconv](http://libav.org/avconv.html)) program has a relatively intuitive
command-line interface, given the diversity and complexity of the
functionality that it exposes, there are still many operations which can be
difficult to express.  I found letterboxing (and pillarboxing) to be one of
those operations, so in order to save others the trouble of working out the
details, this post will develop a command for doing boxing with ffmpeg/avconv.

<!--more-->

### A Quick Note on Terminology

For the unfamiliar, ffmpeg is both a command and the name of the project (more
properly written FFmpeg) which developed the ffmpeg command as well as a
significant amount of other multimedia software.  The avconv program is a
fork of ffmpeg by the Libav project.  The relationship between the two
projects is a bit complex (see [this StackOverflow
question](http://stackoverflow.com/questions/9477115/who-can-tell-me-the-difference-and-relation-between-ffmpeg-libav-and-avconv)
and linked pages for some details), but all commands in this post should work
with either ffmpeg or avconv.  Feel free to use whichever you prefer.

The process that this post is attempting to simplify can be either
letterboxing, adding horizontal bars to an image, or pillarboxing, adding
vertical bars to an image.  From this point forward, either process will
simply be referred to as "boxing".

### Objective

Why would someone want to box video?  My particular motivation is to convert
video for use on mobile devices, which often require video to have particular
resolutions in order to take advantage of hardware acceleration.  When the
aspect ratio of the video does not match the ratio of the desired resolution
it's necessary to either box or stretch the video.  So, I'm boxing it.  More
generally, any time the aspect of the source video does not match the aspect
of the desired output, boxing may be necessary.

### Filtering the Video

Ffmpeg/avconv supports a number of different filters for performing video
manipulation along with a formula evaluation syntax for configuring them.
This allows users to write arithmetic formulas using predefined constants
to specify the behavior of the filters based on values that may change based
on the multimedia input.

Boxing the video will require both the `scale` and `pad` filters, `scale` to
fit the video into the target resolution and `pad` to add the bars.  First,
scaling the video requires calculating the output resolution of the video
without any boxing.  The most desirable output resolution in this case is one
which preserves the source aspect ratio (so the video is not stretched) and
fills the screen either horizontally or vertically.  This can be done by
calculating a scale factor and applying it equally, as follows:

    scale=iw*min($MAX_WIDTH/iw\,$MAX_HEIGHT/ih):ih*min($MAX_WIDTH/iw\,$MAX_HEIGHT/ih)

Note that `$MAX_WIDTH` and `$MAX_HEIGHT` should be replaced with the desired
output width and height, or set as variables in a shell script.  Also note
that the scale factor (the `min` function) is calculated twice.  It could
probably be stored in a variable and reused, but I am not sure of the correct
syntax.  Finally, note that the backslash before the commas is required
because commas are used to separate filters and we are using it to separate
function arguments in this case.

Now that the video has been scaled to fit the desired output resolution, the
`pad` filter can be used to add the appropriate bars.  `pad` requires the
output width and height as well as the offset for where the input should be
placed within the defined output and, optionally, a color.  To add equal-sized
pads simply requires that the offset is half of the size difference between
the output and the input in each dimension, as follows:

    pad=$MAX_WIDTH:$MAX_HEIGHT:(ow-iw)/2:(oh-ih)/2

Again, `$MAX_WIDTH` and `$MAX_HEIGHT` should be replaced with the desired
output width and height, or set as variables in a shell script.

### Dealing with Anamorphic Video (Advanced)

It's possible that the input video is intended to be displayed at a resolution
which has a different aspect ratio than the source file, called an [anamorphic
format](http://en.wikipedia.org/wiki/Anamorphic_format).  I have not had
success playing anamorphic video on mobile devices (probably in part because I
don't really understand it and in part because it is rather esoteric and
poorly supported), so since the video is being scaled anyway this is a great
time to get rid of the anamorphism and make the pixels square.  All that this
requires is to take the Source Aspect Ratio (SAR) into account in the scaling
calculation:

    scale=iw*sar*min($MAX_WIDTH/(iw*sar)\,$MAX_HEIGHT/ih):ih*min($MAX_WIDTH/(iw*sar)\,$MAX_HEIGHT/ih)

### Putting it Together

With the filters defined above, all that is required is to put them together
into a complete command.  It is possible to produce H.264 video with AAC audio
by using the following command:

    avconv \
        -i "$INPUT_FILE" \
        -map 0 \
        -vf "scale=iw*sar*min($MAX_WIDTH/(iw*sar)\,$MAX_HEIGHT/ih):ih*min($MAX_WIDTH/(iw*sar)\,$MAX_HEIGHT/ih),pad=$MAX_WIDTH:$MAX_HEIGHT:(ow-iw)/2:(oh-ih)/2" \
        -c:v libx264 \
        -vprofile baseline -level 30 \
        -c:a libvo_aacenc \
        "$OUTPUT_FILE"

Simply replace `$MAX_WIDTH`, `$MAX_HEIGHT`, `$INPUT_FILE`, and `$OUTPUT_FILE`
(or define them as environment variables) as desired.  That's it.
