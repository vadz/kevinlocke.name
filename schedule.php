<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Schedule",
		      "Kevin Locke's Schedule for Spring semester 2007");
  write_head_stylesheets();
  write_head_close();
?>
<body>
<div id="container">

<?php
include $_SERVER['DOCUMENT_ROOT'].'/include/title.html';
?>

<div id="sidebar">
<?php
include $_SERVER['DOCUMENT_ROOT'].'/include/sidenavbar.html';
echo "\n";

include $_SERVER['DOCUMENT_ROOT'].'/include/news.html';
?>
</div>

<div id="content">
<h2>Weekly Schedule for Fall 2007<br /></h2>
  <table class="schedule">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <th>Monday</th>
        <th>Tuesday</th>
        <th>Wednesday</th>
        <th>Thursday</th>
        <th>Friday</th>
      </tr>
    </thead>

    <tfoot>
      <tr>
	<td colspan="6">Colors Scheme:&nbsp;
	  <span class="lecture">lectures</span>, 
	  <span class="section">sections</span>, 
	  <span class="lab">labs</span>.
	</td>
      </tr>
    </tfoot>

    <tbody>
      <tr>
        <td class="time">9:05-9:55AM</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">10:10-11:00AM</td>
        <td class="lecture" rowspan="2"><a href="http://www.cs.cornell.edu/courses/cs513/2007fa/">CS 513</a></td>
        <td>&nbsp;</td>
        <td class="lecture" rowspan="2"><a href="http://www.cs.cornell.edu/courses/cs513/2007fa/">CS 513</a></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">11:15-12:05PM</td>
	<!-- CS 513 -->
        <td>&nbsp;</td>
	<!-- CS 513 -->
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">12:20-1:10PM</td>
        <td class="lecture"><a href="http://cuinfo.cornell.edu/Academic/Courses/CoSdetail.php?college=AS&amp;number=431%284310%29&amp;prefix=MATH&amp;title=Linear+Algebra+%28MQR%29%2A">MATH 431</a></td>
        <td class="lecture"><a href="http://instruct1.cit.cornell.edu/courses/econ301jpw/">ECON 301</a></td>
        <td class="lecture"><a href="http://cuinfo.cornell.edu/Academic/Courses/CoSdetail.php?college=AS&amp;number=431%284310%29&amp;prefix=MATH&amp;title=Linear+Algebra+%28MQR%29%2A">MATH 431</a></td>
        <td class="lecture"><a href="http://instruct1.cit.cornell.edu/courses/econ301jpw/">ECON 301</a></td>
        <td class="lecture"><a href="http://cuinfo.cornell.edu/Academic/Courses/CoSdetail.php?college=AS&amp;number=431%284310%29&amp;prefix=MATH&amp;title=Linear+Algebra+%28MQR%29%2A">MATH 431</a></td>
      </tr>
      <tr>
        <td class="time">1:25-2:15PM</td>
        <td class="lecture"><a href="http://gdiac.cis.cornell.edu/">CIS 490</a></td>
        <td class="lecture"><a href="http://gdiac.cis.cornell.edu/">CIS 490</a></td>
        <td class="lecture"><a href="http://gdiac.cis.cornell.edu/">CIS 490</a></td>
        <td class="lecture"><a href="http://gdiac.cis.cornell.edu/">CIS 490</a></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">2:30-3:20PM</td>
        <td rowspan="2" class="lecture"><a href="http://cuinfo.cornell.edu/Academic/Courses/CoSdetail.php?college=AS&amp;number=441%284410%29&amp;prefix=MATH&amp;title=Introduction+to+Combinatorics+I+%28MQR%29">MATH 441</a></td>
        <td>&nbsp;</td>
        <td rowspan="2" class="lecture"><a href="http://cuinfo.cornell.edu/Academic/Courses/CoSdetail.php?college=AS&amp;number=441%284410%29&amp;prefix=MATH&amp;title=Introduction+to+Combinatorics+I+%28MQR%29">MATH 441</a></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">3:35-4:25PM</td>
        <!-- MATH 441 -->
        <td>&nbsp;</td>
        <!-- MATH 441 -->
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">4:40-5:30PM</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">5:30-6:20PM</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">6:35-7:25PM</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="time">7:30-9:30PM</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </tbody>
  </table>
  <p>My schedule is also available in <a href="http://www.google.com/calendar/feeds/kwl7@cornell.edu/public/basic">XML</a>
   and <a href="http://www.google.com/calendar/ical/kwl7@cornell.edu/public/basic.ics">iCal</a>
   formats, thanks to <a href="http://www.google.com/calendar/render?cid=kwl7%40cornell.edu"><img src="http://www.google.com/calendar/images/ext/gc_button1_en.gif" alt="Google Calendar" /></a>.</p>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
<!-- vim: set ts=8 sts=2 sw=2 noet: -->
