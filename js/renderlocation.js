function inIframe() {
  try{
    return window.self !== window.top;
  }
  catch (e) {
    return true;
  }
}

if (inIframe()) {
  document.styleSheets[0].insertRule(".navbar-brand, .navbar-brand:nth-child(n), .navbar-right {display: none; !important}", 0);
  document.styleSheets[0].insertRule(".navbar {background-color: #eee !important; border-color: #fff;}", 0);
}

else {
  document.styleSheets[0].insertRule("#searchForm{display: none;}", 0);
}
