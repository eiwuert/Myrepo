var OxOafd2=["onload","contentWindow","idSource","innerHTML","body","document","","designMode","on","contentEditable","fontFamily","style","Tahoma","fontSize","11px","color","black","background","white","length","\x3C$1$3","\x26nbsp;","\x22","\x27","$1","\x26amp;","\x26lt;","\x26gt;","\x26#39;","\x26quot;"];var editor=Window_GetDialogArguments(window); function cancel(){ Window_CloseDialog(window) ;}  ; window[OxOafd2[0x0]]=function (){var iframe=document.getElementById(OxOafd2[0x2])[OxOafd2[0x1]]; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0x3]]=OxOafd2[0x6] ; iframe[OxOafd2[0x5]][OxOafd2[0x7]]=OxOafd2[0x8] ; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0x9]]=true ; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0xb]][OxOafd2[0xa]]=OxOafd2[0xc] ; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0xb]][OxOafd2[0xd]]=OxOafd2[0xe] ; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0xb]][OxOafd2[0xf]]=OxOafd2[0x10] ; iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0xb]][OxOafd2[0x11]]=OxOafd2[0x12] ; iframe.focus() ;}  ; function insertContent(){var iframe=document.getElementById(OxOafd2[0x2])[OxOafd2[0x1]];var Ox1b0=iframe[OxOafd2[0x5]][OxOafd2[0x4]][OxOafd2[0x3]];if(Ox1b0&&Ox1b0[OxOafd2[0x13]]>0x0){ Ox1b0=_CleanCode(Ox1b0) ;if(Ox1b0.match(/<*>/g)){ Ox1b0=String_HtmlEncode(Ox1b0) ;} ; editor.PasteHTML(Ox1b0) ; Window_CloseDialog(window) ;} ;}  ; function _CleanCode(Ox259){ Ox259=Ox259.replace(/<\\?\??xml[^>]>/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<([\w]+) class=([^ |>]*)([^>]*)/gi,OxOafd2[0x14]) ; Ox259=Ox259.replace(/<(\w[^>]*) lang=([^ |>]*)([^>]*)/gi,OxOafd2[0x14]) ; Ox259=Ox259.replace(/\s*mso-[^:]+:[^;"]+;?/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<o:p>\s*<\/o:p>/g,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<o:p>.*?<\/o:p>/g,OxOafd2[0x15]) ; Ox259=Ox259.replace(/<\/?\w+:[^>]*>/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<\!--.*-->/g,OxOafd2[0x6]) ; Ox259=Ox259.replace(/[\\]/gi,OxOafd2[0x16]) ; Ox259=Ox259.replace(/[\\]/gi,OxOafd2[0x17]) ; Ox259=Ox259.replace(/<\\?\?xml[^>]*>/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<(\w+)[^>]*\sstyle="[^"]*DISPLAY\s?:\s?none(.*?)<\/\1>/ig,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<span\s*[^>]*>\s*&nbsp;\s*<\/span>/gi,OxOafd2[0x15]) ; Ox259=Ox259.replace(/<span\s*[^>]*><\/span>/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/\s*style="\s*"/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<([^\s>]+)[^>]*>\s*<\/\1>/g,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<([^\s>]+)[^>]*>\s*<\/\1>/g,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<([^\s>]+)[^>]*>\s*<\/\1>/g,OxOafd2[0x6]) ;while(Ox259.match(/<span\s*>(.*?)<\/span>/gi)){ Ox259=Ox259.replace(/<span\s*>(.*?)<\/span>/gi,OxOafd2[0x18]) ;} ;while(Ox259.match(/<font\s*>(.*?)<\/font>/gi)){ Ox259=Ox259.replace(/<font\s*>(.*?)<\/font>/gi,OxOafd2[0x18]) ;} ; Ox259=Ox259.replace(/<a name="?OLE_LINK\d+"?>((.|[\r\n])*?)<\/a>/gi,OxOafd2[0x18]) ; Ox259=Ox259.replace(/<a name="?_Hlt\d+"?>((.|[\r\n])*?)<\/a>/gi,OxOafd2[0x18]) ; Ox259=Ox259.replace(/<a name="?_Toc\d+"?>((.|[\r\n])*?)<\/a>/gi,OxOafd2[0x18]) ; Ox259=Ox259.replace(/<p([^>])*>(&nbsp;)*\s*<\/p>/gi,OxOafd2[0x6]) ; Ox259=Ox259.replace(/<p([^>])*>(&nbsp;)<\/p>/gi,OxOafd2[0x6]) ;return Ox259;}  ; function String_HtmlEncode(Ox19e){if(Ox19e==null){return OxOafd2[0x6];} ; Ox19e=Ox19e.replace(/&/g,OxOafd2[0x19]) ; Ox19e=Ox19e.replace(/</g,OxOafd2[0x1a]) ; Ox19e=Ox19e.replace(/>/g,OxOafd2[0x1b]) ; Ox19e=Ox19e.replace(/'/g,OxOafd2[0x1c]) ; Ox19e=Ox19e.replace(/\x22/g,OxOafd2[0x1d]) ;return Ox19e;}  ;