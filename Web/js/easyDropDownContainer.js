var exposeMoreInfoState = true
function exposeMoreInfo(ob,text,more,less,pict,moreheight,lessheight) {
  if(exposeMoreInfoState) {
    document.getElementById(ob).style.height = moreheight
    document.getElementById(text).innerHTML = less
    document.getElementById(pict).src = '/images/arrowl.gif'
    exposeMoreInfoState = false
  }
  else {
    document.getElementById(ob).style.height = lessheight
    document.getElementById(text).innerHTML = more
    document.getElementById(pict).src = '/images/arrowd.gif'
    exposeMoreInfoState = true
  }
}
