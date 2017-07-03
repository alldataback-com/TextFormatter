<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Bundles\Forum;
class Renderer extends \s9e\TextFormatter\Renderer
{
	protected $params=['L_HIDE'=>'Hide','L_SHOW'=>'Show','L_SPOILER'=>'Spoiler','L_WROTE'=>'wrote:'];
	protected static $tagBranches=['B'=>0,'BANDCAMP'=>1,'CENTER'=>2,'CODE'=>3,'COLOR'=>4,'DAILYMOTION'=>5,'EMAIL'=>6,'EMOJI'=>7,'FACEBOOK'=>8,'FONT'=>9,'I'=>10,'IMG'=>11,'INDIEGOGO'=>12,'INSTAGRAM'=>13,'KICKSTARTER'=>14,'LI'=>15,'LIST'=>16,'LIVELEAK'=>17,'OL'=>18,'QUOTE'=>19,'S'=>20,'SIZE'=>21,'SOUNDCLOUD'=>22,'SPOILER'=>23,'TABLE'=>24,'TD'=>25,'TH'=>26,'TR'=>27,'TWITCH'=>28,'TWITTER'=>29,'U'=>30,'UL'=>31,'URL'=>32,'VIMEO'=>33,'VINE'=>34,'WSHH'=>35,'YOUTUBE'=>36,'br'=>37,'e'=>38,'i'=>38,'s'=>38,'p'=>39];
	public function __sleep()
	{
		$props = \get_object_vars($this);
		unset($props['out'], $props['proc'], $props['source']);
		return \array_keys($props);
	}
	public function renderRichText($xml)
	{
		if (!isset($this->quickRenderingTest) || !\preg_match($this->quickRenderingTest, $xml))
			try
			{
				return $this->renderQuick($xml);
			}
			catch (\Exception $e)
			{
			}
		$dom = $this->loadXML($xml);
		$this->out = '';
		$this->at($dom->documentElement);
		return $this->out;
	}
	protected function at(\DOMNode $root)
	{
		if ($root->nodeType === 3)
			$this->out .= \htmlspecialchars($root->textContent,0);
		else
			foreach ($root->childNodes as $node)
				if (!isset(self::$tagBranches[$node->nodeName]))
					$this->at($node);
				else
				{
					$tb = self::$tagBranches[$node->nodeName];
					if($tb<20){if($tb<10)if($tb<5)if($tb<3)if($tb===0){$this->out.='<b>';$this->at($node);$this->out.='</b>';}elseif($tb===1){$this->out.='<div data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><div style="overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if($node->hasAttribute('album_id')){$this->out.='album='.\htmlspecialchars($node->getAttribute('album_id'),2);if($node->hasAttribute('track_num'))$this->out.='/t='.\htmlspecialchars($node->getAttribute('track_num'),2);}else$this->out.='track='.\htmlspecialchars($node->getAttribute('track_id'),2);$this->out.='"></iframe></div></div>';}else{$this->out.='<div style="text-align:center">';$this->at($node);$this->out.='</div>';}elseif($tb===3){$this->out.='<pre data-hljs="" data-s9e-livepreview-postprocess="if(\'undefined\'!==typeof hljs)hljs._hb(this)"><code';if($node->hasAttribute('lang'))$this->out.=' class="language-'.\htmlspecialchars($node->getAttribute('lang'),2).'"';$this->out.='>';$this->at($node);$this->out.='</code></pre><script>if("undefined"!==typeof hljs)hljs._ha();else if("undefined"===typeof hljsLoading){hljsLoading=1;var a=document.getElementsByTagName("head")[0],e=document.createElement("link");e.type="text/css";e.rel="stylesheet";e.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/default.min.css";a.appendChild(e);e=document.createElement("script");e.type="text/javascript";e.onload=function(){var d={},f=0;hljs._hb=function(b){b.removeAttribute("data-hljs");var c=b.innerHTML;c in d?b.innerHTML=d[c]:(7<++f&&(d={},f=0),hljs.highlightBlock(b.firstChild),d[c]=b.innerHTML)};hljs._ha=function(){for(var b=document.querySelectorAll("pre[data-hljs]"),c=b.length;0<c;)hljs._hb(b.item(--c))};hljs._ha()};e.async=!0;e.src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js";a.appendChild(e)}</script>';}else{$this->out.='<span style="color:'.\htmlspecialchars($node->getAttribute('color'),2).'">';$this->at($node);$this->out.='</span>';}elseif($tb<8)if($tb===5)$this->out.='<div data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/'.\htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb===6){$this->out.='<a href="mailto:'.\htmlspecialchars($node->getAttribute('email'),2).'">';$this->at($node);$this->out.='</a>';}else$this->out.='<img alt="'.\htmlspecialchars($node->textContent,2).'" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/'.\htmlspecialchars($node->getAttribute('seq'),2).'.png">';elseif($tb===8)$this->out.='<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/facebook.min.html#'.\htmlspecialchars($node->getAttribute('type').$node->getAttribute('id'),2).'" style="border:0;height:360px;max-width:640px;width:100%"></iframe>';else{$this->out.='<span style="font-family:'.\htmlspecialchars($node->getAttribute('font'),2).'">';$this->at($node);$this->out.='</span>';}elseif($tb<15)if($tb<13)if($tb===10){$this->out.='<i>';$this->at($node);$this->out.='</i>';}elseif($tb===11)$this->out.='<img src="'.\htmlspecialchars($node->getAttribute('src'),2).'" title="'.\htmlspecialchars($node->getAttribute('title'),2).'" alt="'.\htmlspecialchars($node->getAttribute('alt'),2).'">';else$this->out.='<div data-s9e-mediaembed="indiegogo" style="display:inline-block;width:100%;max-width:222px"><div style="overflow:hidden;position:relative;padding-bottom:200.45045%"><iframe allowfullscreen="" scrolling="no" src="//www.indiegogo.com/project/'.\htmlspecialchars($node->getAttribute('id'),2).'/embedded" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb===13)$this->out.='<iframe data-s9e-mediaembed="instagram" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/instagram.min.html#'.\htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:640px;max-width:640px;width:100%"></iframe>';elseif($node->hasAttribute('video'))$this->out.='<div data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:480px"><div style="overflow:hidden;position:relative;padding-bottom:75%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.\htmlspecialchars($node->getAttribute('id'),2).'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';else$this->out.='<div data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:220px"><div style="overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.\htmlspecialchars($node->getAttribute('id'),2).'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb<18)if($tb===15){$this->out.='<li>';$this->at($node);$this->out.='</li>';}elseif($tb===16)if(!$node->hasAttribute('type')){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}elseif((\strpos($node->getAttribute('type'),'decimal')===0)||(\strpos($node->getAttribute('type'),'lower')===0)||(\strpos($node->getAttribute('type'),'upper')===0)){$this->out.='<ol style="list-style-type:'.\htmlspecialchars($node->getAttribute('type'),2).'"';if($node->hasAttribute('start'))$this->out.=' start="'.\htmlspecialchars($node->getAttribute('start'),2).'"';$this->out.='>';$this->at($node);$this->out.='</ol>';}else{$this->out.='<ul style="list-style-type:'.\htmlspecialchars($node->getAttribute('type'),2).'">';$this->at($node);$this->out.='</ul>';}else$this->out.='<div data-s9e-mediaembed="liveleak" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.liveleak.com/ll_embed?i='.\htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb===18){$this->out.='<ol>';$this->at($node);$this->out.='</ol>';}else{$this->out.='<blockquote';if(!$node->hasAttribute('author'))$this->out.=' class="uncited"';$this->out.='><div>';if($node->hasAttribute('author'))$this->out.='<cite>'.\htmlspecialchars($node->getAttribute('author'),0).' '.\htmlspecialchars($this->params['L_WROTE'],0).'</cite>';$this->at($node);$this->out.='</div></blockquote>';}}elseif($tb<30)if($tb<25)if($tb<23)if($tb===20){$this->out.='<s>';$this->at($node);$this->out.='</s>';}elseif($tb===21){$this->out.='<span style="font-size:'.\htmlspecialchars($node->getAttribute('size'),2).'px">';$this->at($node);$this->out.='</span>';}else{$this->out.='<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=';if($node->hasAttribute('playlist_id'))$this->out.='https%3A//api.soundcloud.com/playlists/'.\htmlspecialchars($node->getAttribute('playlist_id'),2);elseif($node->hasAttribute('track_id'))$this->out.='https%3A//api.soundcloud.com/tracks/'.\htmlspecialchars($node->getAttribute('track_id'),2).'&amp;secret_token='.\htmlspecialchars($node->getAttribute('secret_token'),2);else{if((\strpos($node->getAttribute('id'),'://')===\false))$this->out.='https%3A//soundcloud.com/';$this->out.=\htmlspecialchars($node->getAttribute('id'),2);}$this->out.='" style="border:0;height:';if($node->hasAttribute('playlist_id')||(\strpos($node->getAttribute('id'),'/sets/')!==\false))$this->out.='450';else$this->out.='166';$this->out.='px;max-width:900px;width:100%"></iframe>';}elseif($tb===23){$this->out.='<div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?\'\':\'none\'"><span>'.\htmlspecialchars($this->params['L_SHOW'],0).'</span><span style="display:none">'.\htmlspecialchars($this->params['L_HIDE'],0).'</span></button><span class="spoiler-title">'.\htmlspecialchars($this->params['L_SPOILER'],0).' '.\htmlspecialchars($node->getAttribute('title'),0).'</span></div><div class="spoiler-content" style="display:none">';$this->at($node);$this->out.='</div></div>';}else{$this->out.='<table>';$this->at($node);$this->out.='</table>';}elseif($tb<28)if($tb===25){$this->out.='<td';if($node->hasAttribute('align'))$this->out.=' style="text-align:'.\htmlspecialchars($node->getAttribute('align'),2).'"';$this->out.='>';$this->at($node);$this->out.='</td>';}elseif($tb===26){$this->out.='<th';if($node->hasAttribute('align'))$this->out.=' style="text-align:'.\htmlspecialchars($node->getAttribute('align'),2).'"';$this->out.='>';$this->at($node);$this->out.='</th>';}else{$this->out.='<tr>';$this->at($node);$this->out.='</tr>';}elseif($tb===28){$this->out.='<div data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="';if($node->hasAttribute('clip_id')){$this->out.='//clips.twitch.tv/embed?autoplay=false&amp;clip=';if($node->hasAttribute('channel'))$this->out.=\htmlspecialchars($node->getAttribute('channel'),2).'/';$this->out.=\htmlspecialchars($node->getAttribute('clip_id'),2);}else{$this->out.='//player.twitch.tv/?autoplay=false&amp;';if($node->hasAttribute('video_id'))$this->out.='video=v'.\htmlspecialchars($node->getAttribute('video_id'),2);else$this->out.='channel='.\htmlspecialchars($node->getAttribute('channel'),2);if($node->hasAttribute('t'))$this->out.='&amp;time='.\htmlspecialchars($node->getAttribute('t'),2);}$this->out.='"></iframe></div></div>';}else$this->out.='<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#'.\htmlspecialchars($node->getAttribute('id'),2).'" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>';elseif($tb<35)if($tb<33)if($tb===30){$this->out.='<u>';$this->at($node);$this->out.='</u>';}elseif($tb===31){$this->out.='<ul>';$this->at($node);$this->out.='</ul>';}else{$this->out.='<a href="'.\htmlspecialchars($node->getAttribute('url'),2).'"';if($node->hasAttribute('title'))$this->out.=' title="'.\htmlspecialchars($node->getAttribute('title'),2).'"';$this->out.='>';$this->at($node);$this->out.='</a>';}elseif($tb===33)$this->out.='<div data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/'.\htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';else$this->out.='<div data-s9e-mediaembed="vine" style="display:inline-block;width:100%;max-width:480px"><div style="overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://vine.co/v/'.\htmlspecialchars($node->getAttribute('id'),2).'/embed/simple?audio=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb<38)if($tb===35)$this->out.='<div data-s9e-mediaembed="wshh" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.worldstarhiphop.com/embed/'.\htmlspecialchars($node->getAttribute('id'),2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';elseif($tb===36){$this->out.='<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.\htmlspecialchars($node->getAttribute('id'),2).'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/'.\htmlspecialchars($node->getAttribute('id'),2);if($node->hasAttribute('list'))$this->out.='?list='.\htmlspecialchars($node->getAttribute('list'),2);if($node->hasAttribute('t')||$node->hasAttribute('m')){if($node->hasAttribute('list'))$this->out.='&amp;';else$this->out.='?';$this->out.='start=';if($node->hasAttribute('t'))$this->out.=\htmlspecialchars($node->getAttribute('t'),2);elseif($node->hasAttribute('h'))$this->out.=\htmlspecialchars($node->getAttribute('h')*3600+$node->getAttribute('m')*60+$node->getAttribute('s'),2);else$this->out.=\htmlspecialchars($node->getAttribute('m')*60+$node->getAttribute('s'),2);}$this->out.='"></iframe></div></div>';}else$this->out.='<br>';elseif($tb===38);else{$this->out.='<p>';$this->at($node);$this->out.='</p>';}
				}
	}
	private static $static=['/B'=>'</b>','/CENTER'=>'</div>','/CODE'=>'</code></pre><script>if("undefined"!==typeof hljs)hljs._ha();else if("undefined"===typeof hljsLoading){hljsLoading=1;var a=document.getElementsByTagName("head")[0],e=document.createElement("link");e.type="text/css";e.rel="stylesheet";e.href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/styles/default.min.css";a.appendChild(e);e=document.createElement("script");e.type="text/javascript";e.onload=function(){var d={},f=0;hljs._hb=function(b){b.removeAttribute("data-hljs");var c=b.innerHTML;c in d?b.innerHTML=d[c]:(7<++f&&(d={},f=0),hljs.highlightBlock(b.firstChild),d[c]=b.innerHTML)};hljs._ha=function(){for(var b=document.querySelectorAll("pre[data-hljs]"),c=b.length;0<c;)hljs._hb(b.item(--c))};hljs._ha()};e.async=!0;e.src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.7.0/highlight.min.js";a.appendChild(e)}</script>','/COLOR'=>'</span>','/EMAIL'=>'</a>','/FONT'=>'</span>','/I'=>'</i>','/LI'=>'</li>','/OL'=>'</ol>','/QUOTE'=>'</div></blockquote>','/S'=>'</s>','/SIZE'=>'</span>','/SPOILER'=>'</div></div>','/TABLE'=>'</table>','/TD'=>'</td>','/TH'=>'</th>','/TR'=>'</tr>','/U'=>'</u>','/UL'=>'</ul>','/URL'=>'</a>','B'=>'<b>','CENTER'=>'<div style="text-align:center">','I'=>'<i>','LI'=>'<li>','OL'=>'<ol>','S'=>'<s>','TABLE'=>'<table>','TR'=>'<tr>','U'=>'<u>','UL'=>'<ul>'];
	private static $dynamic=['COLOR'=>['(^[^ ]+(?> (?!color=)[^=]+="[^"]*")*(?> color="([^"]*)")?.*)s','<span style="color:$1">'],'DAILYMOTION'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>'],'EMAIL'=>['(^[^ ]+(?> (?!email=)[^=]+="[^"]*")*(?> email="([^"]*)")?.*)s','<a href="mailto:$1">'],'FONT'=>['(^[^ ]+(?> (?!font=)[^=]+="[^"]*")*(?> font="([^"]*)")?.*)s','<span style="font-family:$1">'],'IMG'=>['(^[^ ]+(?> (?!(?>alt|src|title)=)[^=]+="[^"]*")*(?> alt="([^"]*)")?(?> (?!(?>src|title)=)[^=]+="[^"]*")*(?> src="([^"]*)")?(?> (?!title=)[^=]+="[^"]*")*(?> title="([^"]*)")?.*)s','<img src="$2" title="$3" alt="$1">'],'INDIEGOGO'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="indiegogo" style="display:inline-block;width:100%;max-width:222px"><div style="overflow:hidden;position:relative;padding-bottom:200.45045%"><iframe allowfullscreen="" scrolling="no" src="//www.indiegogo.com/project/$1/embedded" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>'],'INSTAGRAM'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe data-s9e-mediaembed="instagram" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/instagram.min.html#$1" style="border:0;height:640px;max-width:640px;width:100%"></iframe>'],'LIVELEAK'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="liveleak" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.liveleak.com/ll_embed?i=$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>'],'SIZE'=>['(^[^ ]+(?> (?!size=)[^=]+="[^"]*")*(?> size="([^"]*)")?.*)s','<span style="font-size:$1px">'],'TWITTER'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#$1" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>'],'URL'=>['(^[^ ]+(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s','<a href="$2"$1>'],'VIMEO'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>'],'VINE'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="vine" style="display:inline-block;width:100%;max-width:480px"><div style="overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://vine.co/v/$1/embed/simple?audio=1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>'],'WSHH'=>['(^[^ ]+(?> (?!id=)[^=]+="[^"]*")*(?> id="([^"]*)")?.*)s','<div data-s9e-mediaembed="wshh" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.worldstarhiphop.com/embed/$1" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>']];
	private static $attributes;
	private static $quickBranches=['/LIST'=>0,'BANDCAMP'=>1,'CODE'=>2,'EMOJI'=>3,'FACEBOOK'=>4,'KICKSTARTER'=>5,'LIST'=>6,'QUOTE'=>7,'SOUNDCLOUD'=>8,'SPOILER'=>9,'TD'=>10,'TH'=>11,'TWITCH'=>12,'YOUTUBE'=>13];
	protected function renderQuick($xml)
	{
		$xml = $this->decodeSMP($xml);
		self::$attributes = [];
		$html = \preg_replace_callback(
			'(<(?:(?!/)((?>BANDCAMP|DAILYMOTION|EMOJI|FACEBOOK|I(?>MG|N(?>DIEGOGO|STAGRAM))|KICKSTARTER|LIVELEAK|SOUNDCLOUD|TWIT(?>CH|TER)|VI(?>MEO|NE)|WSHH|YOUTUBE))(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s',
			[$this, 'quick'],
			\preg_replace(
				'(<[eis]>[^<]*</[eis]>)',
				'',
				\substr($xml, 1 + \strpos($xml, '>'), -4)
			)
		);
		return \str_replace('<br/>', '<br>', $html);
	}
	protected function quick($m)
	{
		if (isset($m[2]))
		{
			$id = $m[2];
			if (isset($m[3]))
			{
				unset($m[3]);
				$m[0] = \substr($m[0], 0, -2) . '>';
				$html = $this->quick($m);
				$m[0] = '</' . $id . '>';
				$m[2] = '/' . $id;
				$html .= $this->quick($m);
				return $html;
			}
		}
		else
		{
			$id = $m[1];
			$lpos = 1 + \strpos($m[0], '>');
			$rpos = \strrpos($m[0], '<');
			$textContent = \substr($m[0], $lpos, $rpos - $lpos);
			if (\strpos($textContent, '<') !== \false)
				throw new \RuntimeException;
			$textContent = \htmlspecialchars_decode($textContent);
		}
		if (isset(self::$static[$id]))
			return self::$static[$id];
		if (isset(self::$dynamic[$id]))
		{
			list($match, $replace) = self::$dynamic[$id];
			return \preg_replace($match, $replace, $m[0], 1);
		}
		if (!isset(self::$quickBranches[$id]))
		{
			if ($id[0] === '!' || $id[0] === '?')
				throw new \RuntimeException;
			return '';
		}
		$attributes = [];
		if (\strpos($m[0], '="') !== \false)
		{
			\preg_match_all('(([^ =]++)="([^"]*))S', \substr($m[0], 0, \strpos($m[0], '>')), $matches);
			foreach ($matches[1] as $i => $attrName)
				$attributes[$attrName] = $matches[2][$i];
		}
		$qb = self::$quickBranches[$id];
		if($qb<7)if($qb<4)if($qb===0){$attributes=\array_pop(self::$attributes);$html='';if(!isset($attributes['type']))$html.='</ul>';elseif((\strpos($attributes['type'],'decimal')===0)||(\strpos($attributes['type'],'lower')===0)||(\strpos($attributes['type'],'upper')===0))$html.='</ol>';else$html.='</ul>';}elseif($qb===1){$attributes+=['track_num'=>\null,'track_id'=>\null];$html='<div data-s9e-mediaembed="bandcamp" style="display:inline-block;width:100%;max-width:400px"><div style="overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//bandcamp.com/EmbeddedPlayer/size=large/minimal=true/';if(isset($attributes['album_id'])){$html.='album='.$attributes['album_id'];if(isset($attributes['track_num']))$html.='/t='.$attributes['track_num'];}else$html.='track='.$attributes['track_id'];$html.='"></iframe></div></div>';}elseif($qb===2){$html='<pre data-hljs="" data-s9e-livepreview-postprocess="if(\'undefined\'!==typeof hljs)hljs._hb(this)"><code';if(isset($attributes['lang']))$html.=' class="language-'.$attributes['lang'].'"';$html.='>';}else{$attributes+=['seq'=>\null];$html='<img alt="'.\htmlspecialchars($textContent,2).'" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/'.$attributes['seq'].'.png">';}elseif($qb===4){$attributes+=['type'=>\null,'id'=>\null];$html='<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/facebook.min.html#'.\htmlspecialchars(\htmlspecialchars_decode($attributes['type']).\htmlspecialchars_decode($attributes['id']),2).'" style="border:0;height:360px;max-width:640px;width:100%"></iframe>';}elseif($qb===5){$attributes+=['id'=>\null];$html='';if(isset($attributes['video']))$html.='<div data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:480px"><div style="overflow:hidden;position:relative;padding-bottom:75%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.$attributes['id'].'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';else$html.='<div data-s9e-mediaembed="kickstarter" style="display:inline-block;width:100%;max-width:220px"><div style="overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.$attributes['id'].'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></div></div>';}else{$attributes+=['type'=>\null];$html='';if(!isset($attributes['type']))$html.='<ul>';elseif((\strpos($attributes['type'],'decimal')===0)||(\strpos($attributes['type'],'lower')===0)||(\strpos($attributes['type'],'upper')===0)){$html.='<ol style="list-style-type:'.$attributes['type'].'"';if(isset($attributes['start']))$html.=' start="'.$attributes['start'].'"';$html.='>';}else$html.='<ul style="list-style-type:'.$attributes['type'].'">';self::$attributes[]=$attributes;}elseif($qb<11)if($qb===7){$html='<blockquote';if(!isset($attributes['author']))$html.=' class="uncited"';$html.='><div>';if(isset($attributes['author']))$html.='<cite>'.\str_replace('&quot;','"',$attributes['author']).' '.\htmlspecialchars($this->params['L_WROTE'],0).'</cite>';}elseif($qb===8){$attributes+=['secret_token'=>\null,'id'=>\null];$html='<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=';if(isset($attributes['playlist_id']))$html.='https%3A//api.soundcloud.com/playlists/'.$attributes['playlist_id'];elseif(isset($attributes['track_id']))$html.='https%3A//api.soundcloud.com/tracks/'.$attributes['track_id'].'&amp;secret_token='.$attributes['secret_token'];else{if((\strpos($attributes['id'],'://')===\false))$html.='https%3A//soundcloud.com/';$html.=$attributes['id'];}$html.='" style="border:0;height:';if(isset($attributes['playlist_id'])||(\strpos($attributes['id'],'/sets/')!==\false))$html.='450';else$html.='166';$html.='px;max-width:900px;width:100%"></iframe>';}elseif($qb===9){$attributes+=['title'=>\null];$html='<div class="spoiler"><div class="spoiler-header"><button onclick="var a=parentNode.nextSibling.style,b=firstChild.style,c=lastChild.style;b.display=a.display;a.display=c.display=(b.display)?\'\':\'none\'"><span>'.\htmlspecialchars($this->params['L_SHOW'],0).'</span><span style="display:none">'.\htmlspecialchars($this->params['L_HIDE'],0).'</span></button><span class="spoiler-title">'.\htmlspecialchars($this->params['L_SPOILER'],0).' '.\str_replace('&quot;','"',$attributes['title']).'</span></div><div class="spoiler-content" style="display:none">';}else{$html='<td';if(isset($attributes['align']))$html.=' style="text-align:'.$attributes['align'].'"';$html.='>';}elseif($qb===11){$html='<th';if(isset($attributes['align']))$html.=' style="text-align:'.$attributes['align'].'"';$html.='>';}elseif($qb===12){$attributes+=['channel'=>\null,'clip_id'=>\null];$html='<div data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="';if(isset($attributes['clip_id'])){$html.='//clips.twitch.tv/embed?autoplay=false&amp;clip=';if(isset($attributes['channel']))$html.=$attributes['channel'].'/';$html.=$attributes['clip_id'];}else{$html.='//player.twitch.tv/?autoplay=false&amp;';if(isset($attributes['video_id']))$html.='video=v'.$attributes['video_id'];else$html.='channel='.$attributes['channel'];if(isset($attributes['t']))$html.='&amp;time='.$attributes['t'];}$html.='"></iframe></div></div>';}else{$attributes+=['id'=>\null,'m'=>\null,'s'=>\null];$html='<div data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/'.$attributes['id'].'/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/'.$attributes['id'];if(isset($attributes['list']))$html.='?list='.$attributes['list'];if(isset($attributes['t'])||isset($attributes['m'])){if(isset($attributes['list']))$html.='&amp;';else$html.='?';$html.='start=';if(isset($attributes['t']))$html.=$attributes['t'];elseif(isset($attributes['h']))$html.=\htmlspecialchars($attributes['h']*3600+$attributes['m']*60+$attributes['s'],2);else$html.=\htmlspecialchars($attributes['m']*60+$attributes['s'],2);}$html.='"></iframe></div></div>';}
		return $html;
	}
}