<?php
$help = array();

$help['Instructions']=<<<INTRO

<p>
	When you have a fresh install of WordPRess and you&#8217;re about to enter content, this plugin can save you a lot of time by automatically creating blank pages/posts/custom post type.
</p>
<p>
	It can also create a navigation menu based on your site structure so you don&#8217;t have to manually create one later. All you need to do is provide a menu name and the plugin handles the rest.
</p>

<p><strong>How to build a skeleton:</strong></p>

<ol>
	<li>Get your <abbr title="This could be a doc, docx, txt, etc. This plugin just needs an ordered list.">sitemap file</abbr>, open it, and copy the contents to your clipboard.</li>
	<li>Paste your sitemap into the Skeleton box.</li>
	<li>You can now drag &amp; drop the site tree on the right to make any adjustments.</li>
	<li>You can also expand each item to customize them further.</li>
	<li>Once you&#8217;re happy with your sitemap, press the build skeleton button.</li>
	<li>Once you&#8217;re done, you can disable and uninstall this plugin.</li>
</ol>

INTRO;


/*
$help['Skeleton Dash Format']=<<<DASH_FORMAT

<p>
	This is the original skeleton format I used since version 0.1. Its very simple and easy to use, so I'll try to always support it.
</p>
<p>
	The skeleton is a series of slug:title pairs (one per line). If the title is missing, the capitalized slug will be used as the page title. You can use the dash (-) before the slug to indicate depth.
</p>

<p><strong>Example Skeleton - Dash</strong></p>
<p>
	home<br />
	about<br />
	-staff<br />
	--jobs<br />
	-our-office:View Our Office<br />
	contact:Contact Us
</p>

DASH_FORMAT;
*/


$help['Formats']=<<<FORMATS

<p>
	When you copy &amp; paste a nested list from a word processor (MS Word, Open Office, Libre Office) this is what it looks like when you paste it in the Skeleton textarea below.
</p>
<p>
	This plugin will parse this for you and generate a skeleton from it. We've tried to account for as many different variations in list formatting, such as alpha, numeric, and roman numeral.
	This plugin does not support non alphanumeric list markers (dots, squares, etc.).
</p>

<p><strong>Example Skeleton - MS Word</strong></p>
<p>
	1.	Home<br />
	2.	About Us<br />
	a.	Photos<br />
	3.	Services<br />
	a.	Web Design<br />
	b.	Web Development<br />
	i.	Wordpress<br />
	1.	Plugins<br />
	4.	Contact Us
</p>

FORMATS;



$help['Drag &amp; Drop']=<<<DRAG_DROP

<p>
	If you have a browser that supports drag &amp; drop events, you can drag a text file containing your skeleton into the textarea. Threre is no DOC or DOCX support at this time, only TXT.
</p>
<p>
	The sortable tree will automatically show up so you can make any adjustments to your skeleton before you build it.
</p>

DRAG_DROP;

// https://secure.gravatar.com/site/check/eric@webdeveric.com


$help['Contact Us']=<<<CONTACT_US

<p>
	<strong>Plugin Authors:</strong>
</p>
<ul id="author-cards">
	<li>
		<a href="http://webdeveric.com/" target="_blank"><img src="https://secure.gravatar.com/avatar/ede607c628f145b73779d4e9583cab73?s=100" width="100" height="100" alt="Eric King" title="" class="alignleft author-image" /></a>
		<div class="author-info">
			<h2>Eric King</h2>
			<div class="social-icons">
				<a data-icon="&#xe003;" href="https://twitter.com/webdeveric" target="_blank"></a>
				<a data-icon="&#xe011;" href="http://git.webdeveric.com/" target="_blank"></a>
			</div>
			<dl>
				<dt>Website</dt>
				<dd><a href="http://phplug.in/" target="_blank">phplug.in</a></dd>
				<dd><a href="http://webdeveric.com/" target="_blank">webdeveric.com</a></dd>				
			</dl>
		</div>
	</li><li>
		<a href="http://timwickstrom.com/" target="_blank"><img src="https://secure.gravatar.com/avatar/0d25e7ddda1b06661b915c8b5fd4188f?s=100" width="100" height="100" alt="Tim Wickstrom" title="" class="alignleft author-image" /></a>
		<div class="author-info">
			<h2>Tim Wickstrom</h2>
			<div class="social-icons">
				<a data-icon="&#xe003;" href="https://twitter.com/timwickstrom" target="_blank"></a>
				<a data-icon="&#xe019;" href="https://github.com/twickstrom" target="_blank"></a>
			</div>
			<dl>
				<dt>Website</dt>
				<dd><a href="http://timwickstrom.com/" target="_blank">timwickstrom.com</a></dd>
			</dl>
		</div>
	</li>
</ul>

CONTACT_US;

return $help;
