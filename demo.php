<?php

include 'search_replace.php';

if ( isset( $_POST['test'] ) && $_POST['test'] && $_POST['path'] != '' )
{
    if ( !isset( $_POST['recursive'] ) ) $_POST['recursive'] = 0;
    if ( !isset( $_POST['whole'] ) ) $_POST['whole'] = 0;
    if ( !isset( $_POST['case'] ) ) $_POST['case'] = 0;
    if ( !isset( $_POST['regexp'] ) ) $_POST['regexp'] = 0;

    $test = new search_replace( $_POST['path'],
                                $_POST['needle'],
                                $_POST['replace'],
                                $_POST['ext'],
                                $_POST['regexp'],
                                $_POST['recursive'],
                                $_POST['whole'],
                                $_POST['case'] );



    $result = $test->print_results();
   // print_r( $test );
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Text Based File Search &amp; Replace</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
<!--
body
{
	min-width: 800px;
	margin: 0 3%;
	background: #fff;
	color: #000;
	font-family: verdana,arial,sans-serif;
	font-size: 75%;
}
#container
{
	background: #efefef;
    border: 1px solid #ccc;
	-moz-border-radius: 5px;
    border-radius: 5px;
	padding: 5px;
    clear: both;
}
#header
{
	margin-top: 0;
	margin-bottom: 20px;
	padding: 10px;
	-moz-border-radius: 6px;
    border-bottom-right-radius: 6px;
	border-bottom-left-radius: 6px;
	box-shadow: 0px 3px 1px #ccc;
	background: #383838;
	/* gecko based browsers */
	background: -moz-linear-gradient(top, #444, #000);
	/* webkit based browsers */
	background: -webkit-gradient(linear, left top, left bottom, from(#444), to(#000));
	color: #fff; /* text colour (black) */
	height: auto; /* gradient uses the full height of the element */
}
#form_table
{
	width: 100%;
	border-collapse: seperate;
	border-spacing: 8px;
}
#form_table td
{
	-moz-border-radius: 3px;
    border-radius: 3px;
	background: #ddd;
	color: #000;
	padding: 5px;
}
input
{
    padding: 3px;
}
.align_elem
{
    text-align: left;
}
.align_ctrl
{
    text-align: center;
}
-->
</style>
</head>
<body>
	<div id="header">
		<h1 style="font-style:oblique;padding-left:20px;">Text Based File Search &amp; Replace</h1>
	</div>
	<div id="container">
        <form action="demo.php" method="post">
            <input type="hidden" name="test" value="1" />
            <table id="form_table">
                <tr>
                    <th class="align_elem" colspan="2">PHP Search and Replace is a PHP 5 class that can search and replace strings in multiple text based files and subdirectories.<br /><br />
                    </th>
                </tr>
                <tr>
                    <th class="align_elem" colspan="2">Enter Directory Path</th>
                </tr>
                <tr>
                    <td class="align_elem"><input type="text" name="path" /></td>
                    <td class="align_elem" style="width:100%;">Enter absolute or relative path - ie: public_html/scripts - C:\Apache\htdocs\scripts</td>
                </tr>
                <tr>
                    <th class="align_elem" colspan="2">Enter Search String</th>
                </tr>
                <tr>
                    <td class="align_elem"><textarea name="needle" /></textarea></td>
                    <td class="align_elem">Enter string to search for</td>
                </tr>
                <tr>
                    <th class="align_elem" colspan="2">Regular Expression</th>
                </tr>
                <tr>
                    <td class="align_ctrl"><input type="checkbox" name="regexp" value="1" />&nbsp;&nbsp;Check to enable</td>
                    <td class="align_elem">Search with regular expression - overides "Match Whole Word"</td>
                </tr>
                <tr>
                    <th class="align_elem" colspan="2">Enter Replacement String</th>
                </tr>
                <tr>
                    <td class="align_elem"><textarea name="replace" /></textarea></td>
                    <td class="align_elem">Enter replacement string</td>
                </tr>
                 <tr>
                    <th class="align_elem" colspan="2">Exclusive File Types</th>
                </tr>
                <tr>
                    <td class="align_elem"><input type="text" name="ext" value="" /></td>
                    <td class="align_elem">Enter exclusive extension(s) to search - separate with comma - ie: php,css,sql</td>
                </tr>
                 <tr>
                    <th class="align_elem" colspan="2">Recursive Search</th>
                </tr>
                <tr>
                    <td class="align_ctrl"><input type="checkbox" name="recursive" value="1" />&nbsp;&nbsp;Check to enable</td>
                    <td class="align_elem">Perform recursive search on subdirectories</td>
                </tr>
                 <tr>
                    <th class="align_elem" colspan="2">Match Whole Word</th>
                </tr>
                <tr>
                    <td class="align_ctrl"><input type="checkbox" name="whole" value="1" />&nbsp;&nbsp;Check to enable</td>
                    <td class="align_elem">Match whole word only</td>
                </tr>
                 <tr>
                    <th class="align_elem" colspan="2">Case Sensitive Match</th>
                </tr>
                <tr>
                    <td class="align_ctrl"><input type="checkbox" name="case" value="1" />&nbsp;&nbsp;Check to enable</td>
                    <td class="align_elem">Match by proper case</td>
                </tr>
                <tr>
                    <th colspan="2">&nbsp;</th>
                </tr>
                <tr>
                    <td colspan="2" class="align_ctrl"><input type="submit" value="Replace" style="padding:5px 20px;" />&nbsp;&nbsp;&nbsp;<input type="reset"style="padding:5px 16px;" value="Clear Form" /></td>
                </tr>
                <tr>
                    <th class="align_elem" colspan="2" style="padding:10px;">
                    <?php

                    if ( isset( $_POST['test'] ) && $_POST['test'] && $_POST['path'] != '' )
                    {
                        echo "Search string: <span style=\"font-weight:normal\">{$_POST['needle']}</span><br />";
                        echo "Replacement string: <span style=\"font-weight:normal\">{$_POST['replace']}</span><br />";
                        echo "<p>Files searched: {$result[0]}<br />";
                        echo "Files modified: {$result[1]}</p>";

                        echo "<p style=\"height:100px;overflow:auto;background:#fff;padding:8px;border:1px solid #ccc;\">";

                        if ( $result[1] )
                        {
                            foreach ( $result[2] as $files )
                                echo $files . ' instance(s)<br />';
                        }

                        echo '</p>';
                    }
                    else
                    {
                        echo "<p>Files searched:<br />";
                        echo "Files modified:</p>";
                    }

                    ?>
                    </th>
                </tr>
            </table>
		</form>
	</div>
</body>
</html>
