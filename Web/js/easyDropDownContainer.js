var exposeMoreInfoState = true
function exposeMoreInfo(ob,text,more,less,pict,fullheight) {
  if(exposeMoreInfoState) {
    document.getElementById(ob).style.height = fullheight
    document.getElementById(text).innerHTML = less
    document.getElementById(pict).src = 'images/arrowl.gif'
    exposeMoreInfoState = false
  }
  else {
    document.getElementById(ob).style.height = '25px'
    document.getElementById(text).innerHTML = more
    document.getElementById(pict).src = 'images/arrowd.gif'
    exposeMoreInfoState = true
  }
}
