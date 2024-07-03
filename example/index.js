function $(e){return document.getElementById(e);}
var storage=window.localStorage;ons.platform.select('ios');window.app={};window.app.param={};window.app.toggleMenu=function(){$('appSplitter').right.toggle();};window.app.loadView=function(index){$('appTabbar').setActiveTab(index);$('sidemenu').close();};window.app.loadLink=function(u){window.open(u,'_blank');};window.app.pushPage=function(url,page,anim){if(anim){$('appNavigator').pushPage(url,{data:page,animation:anim});}else{$('appNavigator').pushPage(url,{data:page});}};window.app.pushPageEx=function(url,t,i,anim){if(anim){$('appNavigator').pushPage(url,{data:{title:t,id:i},animation:anim});}else{$('appNavigator').pushPage(url,{data:{title:t,id:i}});}};window.app.replacePage=function(url,page){$('appNavigator').replacePage(url,{data:page,animation:'fade'});};window.app.setParam=function(param){window.app.param=param;appNavigator.popPage();};ons.ready(function(){const sidemenu=$('appSplitter');if(sidemenu.right)ons.platform.isAndroid()?sidemenu.right.setAttribute('animation','overlay'):sidemenu.right.setAttribute('animation','reveal');document.querySelector('#tabbar-page').addEventListener('postchange',function(event){if(event.target.matches('#appTabbar')){event.currentTarget.querySelector('ons-toolbar .center').innerHTML=event.tabItem.getAttribute('label');}});});(function(){if(typeof NodeList.prototype.forEach==="function")return false;NodeList.prototype.forEach=Array.prototype.forEach;})();function xua_uniqueObjectArray(objArray,propName)
{var storage={};var uniqueObjArray=[];var i,value;for(i=0;i<objArray.length;i++){value=objArray[i][propName];if(!(value in storage)){storage[value]=true;uniqueObjArray.push(objArray[i]);}}
return uniqueObjArray;}
function getCookie(name)
{if(!name||!document.cookie)return;var cookies=document.cookie.split("; ");for(var i=0;i<cookies.length;i++){var str=cookies[i].split("=");if(str[0]!=name)continue;return unescape(str[1]);}
return;}
function removeCookie(name)
{if(!name||!document.cookie)return;document.cookie=name+'=; max-age=0; path=/';}
function saveFile(name,stream)
{if(window.navigator.msSaveBlob){window.navigator.msSaveBlob(new Blob([stream],{type:"text/plain"}),name);}else{var a=document.createElement("a");a.href=URL.createObjectURL(new Blob([stream],{type:"text/plain"}));a.download=name;document.body.appendChild(a)
a.click();document.body.removeChild(a)}}
function msg(t,s){ons.notification.alert({message:s,title:t});}
function emsg(e){console.log(e);ons.notification.alert({message:'情報の更新に失敗しました。',title:'ばぐっ！'});}
var weeks=['日','月','火','水','木','金','土'];function findData(data,id){return data[findKey(data,id)];}
function findKey(data,id){for(var key=0;key<data.length;key++){if(data[key].id==id)return key;}}
var api=axios.create({baseURL:axios_url,withCredentials:true});api.interceptors.response.use(function(response){if(response.headers['x-xsrf-token']){document.cookie='XSRF-TOKEN='+response.headers['x-xsrf-token']+'; path=/';}
return response;});function createCSV(tbl){api.get('/'+tbl).then(function(response){let r=php_crud_api_transform(response.data)[tbl];var s="";for(array of r){Object.keys(array).forEach(function(key){let r=''+array[key];r=r.replace(/"/g,'""');if(r.search(/("|,|\n)/g)>=0)r='"'+r+'"';s+=r+',';});s=s.slice(0,-1)+'\r\n';}
saveFile(tbl+'.csv',s);});}
var user;var Auth={loggedIn:false,login:function(){api.post('/',{token:token}).then(function(response){api.get('/users').then(function(response){user=php_crud_api_transform(response.data).users;}).catch(function(e){Auth.logout();});this.loggedIn=true;storage.setItem('AUTH-TOKEN',token);}).catch(function(e){msg('認証失敗！','認証できませんでした。<br>もう一度入力してください。<br>');Auth.logout();});},logout:function(){msg('お知らせ♪','サインアウトしました。');storage.clear();this.loggedIn=false;removeCookie('AUTH-TOKEN');location='logout.php';}};var token=getCookie('AUTH-TOKEN');if(!token)token=storage.getItem('AUTH-TOKEN');Auth.login();function removeData(table,id,msg){ons.notification.confirm({title:'',message:msg,callback:function(n){if(!n)return;api.delete(table+id).then(function(response){appNavigator.popPage();}).catch(function(e){emsg(e);});}});}
function createPull(pullHook,icon,func){pullHook.addEventListener('changestate',function(event){switch(event.state){case 'initial':icon.setAttribute('icon','fa-arrow-down');icon.removeAttribute('rotate');icon.removeAttribute('spin');break;case 'preaction':icon.setAttribute('icon','fa-arrow-down');icon.setAttribute('rotate','180');icon.removeAttribute('spin');break;case 'action':icon.setAttribute('icon','fa-spinner');icon.removeAttribute('rotate');icon.setAttribute('spin',true);break;}});pullHook.onAction=function(done){setTimeout(()=>{func();done();},400);}}
function getCsv(url){var txt=new XMLHttpRequest();txt.open('get',url,false);txt.send();var arr=txt.responseText.split('\n');var res=[];for(let i=0;i<arr.length;i++){if(arr[i]=='')break;res[i]=arr[i].split(',');for(let i2=0;i2<res[i].length;i2++){if(res[i][i2].match(/\-?\d+(.\d+)?(e[\+\-]d+)?/)){res[i][i2]=parseFloat(res[i][i2].replace('"',''));}}}
return res;}
function updateValue(tbl,id,fname){const onlyInputs=document.querySelectorAll('#'+fname+' input');const obj={};onlyInputs.forEach(input=>{obj[input.name]=input.value;});api.put('/'+tbl+'/'+id,obj).then(function(response){ons.notification.alert({message:'更新しました♪',title:''});}).catch(function(e){emsg(e);});appNavigator.popPage();}
function removeValue(tbl,id){removeData('/'+tbl+'/',id,'削除しますか？ [#'+id+']');}