var zt=Object.defineProperty;var Xe=Object.getOwnPropertySymbols;var Mt=Object.prototype.hasOwnProperty,Bt=Object.prototype.propertyIsEnumerable;var Ye=(p,E,h)=>E in p?zt(p,E,{enumerable:!0,configurable:!0,writable:!0,value:h}):p[E]=h,Q=(p,E)=>{for(var h in E||(E={}))Mt.call(E,h)&&Ye(p,h,E[h]);if(Xe)for(var h of Xe(E))Bt.call(E,h)&&Ye(p,h,E[h]);return p};var w=(p,E,h)=>new Promise((re,H)=>{var Y=U=>{try{W(h.next(U))}catch(J){H(J)}},oe=U=>{try{W(h.throw(U))}catch(J){H(J)}},W=U=>U.done?re(U.value):Promise.resolve(U.value).then(Y,oe);W((h=h.apply(p,E)).next())});import{a as R,d as Ze,r as g,l as X,c as _e,o as Jt,O as jt,b as y,k as c,g as o,G as a,I as B,f as l,x as m,e as n,Q as q,j as A,h as fe,t as x,R as Kt,H as qt,F as T,i as O,E as S,ax as N,p as Pe}from"./index-BY52ryft.js";/* empty css                 *//* empty css                        *//* empty css                  */import{E as Ht}from"./el-dialog-DW2f2j_a.js";import"./el-overlay-C-PNe3oI.js";import{E as Gt}from"./el-drawer-OrYF01kL.js";import{E as Qt}from"./el-segmented-BSJ535cw.js";import{E as Xt}from"./el-divider-BMVe0vJE.js";import{a as Yt,E as Zt}from"./el-tab-pane-BaYk9vZQ.js";/* empty css                *//* empty css               */import{E as el,a as tl}from"./el-select-DRruM0zx.js";import"./el-scrollbar-DkeX7uWN.js";import"./el-popper-CTRlU7Sy.js";/* empty css                     */import{E as ll}from"./el-pagination-DI0RG0NL.js";import{E as rl}from"./el-card-BucRKLDy.js";import{a as ol,E as al}from"./el-col-CtbKq4-p.js";import{_ as nl}from"./index.vue_vue_type_script_setup_true_lang-Dm75BEdM.js";/* empty css               *//* empty css                */import{E as Te}from"./overlay-DhlEve-O.js";/* empty css              *//* empty css              *//* empty css                 *//* empty css                *//* empty css               *//* empty css                  *//* empty css            */import{E as sl}from"./index-Dx0TKURZ.js";import{E as _}from"./index-e5v7-KDR.js";import{E as et,a as P}from"./index-ojJLRbX-.js";import{E as le}from"./index-DebtYkZV.js";import{E as il,a as ul}from"./index-iFU4vIRg.js";import{E as dl}from"./index-CEkE8Z_g.js";import{E as pl}from"./index-KhPdEs2H.js";import{E as cl}from"./index-DTTUs0dC.js";import{_ as ml}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./index-DrUcg1UD.js";import"./vnode-DTOCuIRg.js";import"./error-yRVTQ7SO.js";import"./scroll-CBaX7Sx4.js";import"./focus-trap-CvFpoWm2.js";import"./aria-BAV2N4oM.js";import"./index-C_RANDN9.js";import"./index-DXZB4pyF.js";import"./refs-D6FW0B1r.js";import"./index-Ch0o4GyK.js";import"./index-40-JtBeM.js";import"./index-DM_yc6Lu.js";import"./use-form-item-CvHT10-s.js";import"./raf-zYSLYssI.js";import"./_initCloneObject-DKmtBixW.js";import"./strings-Dui6pOJV.js";import"./clamp-DK2vXM0z.js";import"./toNumber-BPKCgy1r.js";import"./index-D10w3HDi.js";import"./_baseClone-BoTqKQnr.js";import"./castArray-DuoG_JVm.js";import"./debounce-DA5a7ohM.js";import"./_baseIteratee-ydFuDNAl.js";import"./index-Jynb_o7C.js";import"./index-DdS5waJA.js";import"./validator-IHcpmMc8.js";import"./index-V2l4Tptj.js";import"./index-Cnok9s67.js";function gl(){return R.get({url:"/admin/cloudexport/getConfig",successCodes:[1],rawResponse:!0})}function _l(p){return R.post({url:"/admin/cloudexport/saveConfig",params:{token:p},successCodes:[1],rawResponse:!0})}function fl(){return R.get({url:"/admin/cloudexport/getOptions",successCodes:[1],rawResponse:!0})}function yl(p){return R.get({url:"/admin/cloudexport/getList",params:p,successCodes:[1],rawResponse:!0})}function bl(p){return R.post({url:"/admin/cloudexport/saveItem",params:p,successCodes:[1],rawResponse:!0})}function vl(p){return R.post({url:"/admin/cloudexport/copyItem",params:{id:p},successCodes:[1],rawResponse:!0})}function kl(p){return R.post({url:"/admin/cloudexport/deleteItem",params:{id:p},successCodes:[1],rawResponse:!0})}function xl(p){return R.post({url:"/admin/cloudexport/triggerConfigPush",params:{config_id:p},successCodes:[1],rawResponse:!0})}function hl(p){return R.post({url:"/admin/cloudexport/triggerCallbackSync",params:{config_id:p},successCodes:[1],rawResponse:!0})}function Cl(p){return R.get({url:"/admin/cloudexport/getCallbackPyScript",params:{id:p},successCodes:[1],rawResponse:!0})}function wl(p){return R.get({url:"/admin/cloudexport/getCallbackCronConfig",params:{id:p},successCodes:[1],rawResponse:!0})}function Sl(p){return R.post({url:"/admin/cloudexport/saveCallbackCronConfig",params:p,successCodes:[1],rawResponse:!0})}function El(p){return R.get({url:"/admin/cloudexport/getPushLogs",params:p,successCodes:[1],rawResponse:!0})}function Vl(p){return R.get({url:"/admin/cloudexport/getCallbackLogs",params:p,successCodes:[1],rawResponse:!0})}function Pl(p){return R.post({url:"/admin/cloudexport/retryPushLog",params:{id:p},successCodes:[0,1],rawResponse:!0})}const Tl={class:"admin-cloud-export-page"},Rl={class:"card-header"},Il={class:"card-header"},Nl={class:"product-list"},Ul={class:"product-text"},Al={class:"pagination-wrap"},Ol={class:"pagination-wrap"},Wl={class:"config-section-panel"},Ll={class:"config-section-panel"},Dl={class:"status-map-table"},Fl={class:"log-detail"},$l={class:"log-detail-table"},zl={class:"log-json-title"},Ml={class:"json-preview"},Bl={class:"all-product-list"},Jl={class:"cron-readonly-text"},jl={class:"example-dialog"},Kl={class:"example-section"},ql={class:"example-code"},Hl={key:1,class:"example-section"},Gl={class:"guide-grid"},Ql={class:"example-code example-code-large"},Xl={class:"example-section"},Yl={class:"guide-grid"},Zl={class:"guide-grid"},er={class:"example-section"},tr={class:"example-code"},lr={class:"example-section"},rr={class:"example-code example-code-large"},or=Ze({name:"AdminCloudExportIndex",__name:"index",setup(p){const E=g("config"),h=g("push"),re=g(!1),H=g(!1),Y=g(!1),oe=g(!1),W=g(!1),U=g(!1),J=g(!1),Z=g(!1),ye=g(!1),be=g(!1),de=g(!1),ve=g(!1),ke=g("push"),Re=g(""),ae=g(""),ee=g(""),xe=g(""),Ie=g([]),pe=g([]),Ne=g([]),Ue=g([]),Ae=g([]),Oe=g(null),We=g([]),he=g(null),ne=g(),L=X({sourceCol:"",targetCol:"",orderCol:"",script:""}),I=X({triggerBody:"",pyScript:"",fieldRows:[],statusRows:[]}),b=X({enabled:!1,interval:5,batchSize:50,lastTime:"",lastResult:""}),j=X({page:1,limit:20,total:0}),D=X({page:1,limit:20,total:0}),$=X({order_no:"",status:""}),K=[{key:"order_no",label:"订单号",placeholder:"WPS表中订单号对应列名"},{key:"order_create_time",label:"订单创建时间",placeholder:"WPS表中订单创建时间对应列名"},{key:"product_name",label:"产品名称",placeholder:"WPS表中产品名称对应列名"},{key:"customer_name",label:"姓名",placeholder:"WPS表中姓名对应列名"},{key:"phone",label:"电话",placeholder:"WPS表中电话对应列名"},{key:"idcard",label:"证件号",placeholder:"WPS表中证件号对应列名"},{key:"address",label:"地址",placeholder:"WPS表中地址对应列名"},{key:"id_card_photos",label:"证件照片",placeholder:"WPS表中证件照片对应列名"},{key:"photo_reupload_count",label:"照片重传次数",placeholder:"WPS表中照片重传次数对应列名"},{key:"custom_order_fields",label:"自定义字段",placeholder:"WPS表中自定义字段对应列名"},{key:"production_number",label:"生产号码",placeholder:"WPS表中生产号码对应列名"},{key:"iccid",label:"ICCID",placeholder:"WPS表中ICCID对应列名"},{key:"puk",label:"PUK",placeholder:"WPS表中PUK对应列名"}],ce=[{key:"sync_order_no_col",label:"订单号",placeholder:"WPS表中订单号对应列名"},{key:"sync_production_number_col",label:"生产号码",placeholder:"WPS表中生产号码对应列名"},{key:"sync_iccid_col",label:"ICCID",placeholder:"WPS表中ICCID对应列名"},{key:"sync_puk_col",label:"PUK",placeholder:"WPS表中PUK对应列名"},{key:"sync_express_company_col",label:"快递公司",placeholder:"WPS表中快递公司对应列名"},{key:"sync_tracking_number_col",label:"快递单号",placeholder:"WPS表中快递单号对应列名"},{key:"sync_remark_col",label:"备注",placeholder:"WPS表中备注对应列名"},{key:"sync_order_status_col",label:"订单状态",placeholder:"WPS表中订单状态对应列名"}],se=[{key:"sync_status_map_0",label:"已提交"},{key:"sync_status_map_7",label:"审核失败"},{key:"sync_status_map_1",label:"待发货"},{key:"sync_status_map_2",label:"已发货"},{key:"sync_status_map_3",label:"待传照片"},{key:"sync_status_map_4",label:"已激活"},{key:"sync_status_map_5",label:"已结算"},{key:"sync_status_map_6",label:"结算失败"}],tt=_e(()=>{const t=[];for(let e=0;e<se.length;e+=2)t.push(se.slice(e,e+2));return t}),Le=()=>ce.reduce((t,e)=>(t[e.key]="",t),{}),De=()=>se.reduce((t,e)=>(t[e.key]="",t),{}),Fe=(t=K.map(e=>e.key))=>K.reduce((e,s)=>(t.includes(s.key)&&(e[s.key]=s.label),e),{}),i=X(Q(Q({id:0,export_mode:"channel_product",channel_key:"",product_ids:[],table_name:"",sheet_name:"",push_webhook_url:"",callback_trigger_webhook_url:"",remark:"",export_fields:K.map(t=>t.key),export_col_map:Fe()},Le()),De())),lt={channel_key:[{validator:(t,e,s)=>{if(i.export_mode!=="all"&&!e){s(new Error("请选择渠道"));return}s()},trigger:"change"}],table_name:[{required:!0,message:"请输入表格名称",trigger:"blur"}],sheet_name:[{required:!0,message:"请输入 Sheet 名称",trigger:"blur"}]},rt=_e(()=>i.export_mode==="all"||!i.channel_key?pe.value:pe.value.filter(t=>t.channel_key===i.channel_key)),ot=t=>{if(t==null||t==="")return"";if(typeof t=="string")try{return JSON.stringify(JSON.parse(t),null,2)}catch(e){return t}try{return JSON.stringify(t,null,2)}catch(e){return String(t)}},at={id:"ID",config_id:"配置ID",order_id:"订单ID",order_no:"订单号",channel_name:"渠道",event_type:"事件",trigger_source:"来源",webhook_url:"Webhook地址",request_body:"请求体",response_body:"响应体",parsed_row:"解析数据",status:"状态",message:"结果",http_code:"HTTP状态码",retry_count:"重试次数",created_time:"时间"},$e=()=>{const t=Oe.value||{},e=["request_body","response_body","parsed_row"],s=["id","config_id","order_id","order_no","channel_name","event_type","trigger_source","webhook_url","request_body","response_body","parsed_row","status","message","http_code","retry_count","created_time"];return[...s,...Object.keys(t).filter(u=>!s.includes(u))].filter(u=>Object.prototype.hasOwnProperty.call(t,u)).map(u=>{const f=t[u];let C=e.includes(u)?ot(f):String(f!=null?f:"");return u==="event_type"&&(C=ze(C)),u==="trigger_source"&&(C=Me(C)),u==="status"&&(C=Be(C)),{key:u,label:at[u]||u,value:C,isJson:e.includes(u)}})},nt=_e(()=>$e().filter(t=>!t.isJson)),st=_e(()=>$e().filter(t=>t.isJson)),Ce=t=>{const e=String(t.product_name||"").trim();return e?e.split(/[、,\n\r]+/u).map(s=>s.trim()).filter(Boolean):["全部产品"]},it=t=>{We.value=Ce(t),J.value=!0},ut=()=>{var d;const t="ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";let e="";const s=new Uint8Array(32);(d=window.crypto)==null||d.getRandomValues(s);for(let u=0;u<s.length;u++)e+=t[s[u]%t.length];ee.value=e},ze=t=>({manual_push:"手动推送",manual_config_push:"手动推送",retry_push:"重试推送",photo_reupload:"照片重传",order_created:"订单创建",order_status_changed:"订单状态变更",order_updated:"订单更新"})[String(t||"")]||t||"-",Me=t=>({admin_order_list:"后台订单列表",admin_config_list:"后台配置列表",admin_history_retry:"后台历史重试",history_retry:"后台历史重试",shop_local_order:"店铺下单",admin_reupload:"后台重传照片",open_order_status:"开放接口状态更新",order_callback_service:"订单回调服务",system_timer:"系统定时任务"})[String(t||"")]||t||"-",Be=t=>({success:"成功",failed:"失败",pending:"待处理"})[String(t||"")]||t||"-",Je=t=>i.export_fields.includes(t),dt=t=>{const e=i.export_fields.indexOf(t.key);if(e>=0){i.export_fields.splice(e,1);return}if(!String(i.export_col_map[t.key]||"").trim()){S.warning(`请先输入${t.label}列标题`);return}i.export_fields.push(t.key)},pt=()=>({order_no:"订单号",order_create_time:"订单创建时间",product_name:"产品名称",customer_name:"姓名",phone:"电话",idcard:"证件号",address:"地址",id_card_photos:"证件照片",photo_reupload_count:"照片重传次数",custom_order_fields:"自定义字段",production_number:"生产号码",iccid:"ICCID",puk:"PUK"}),te=t=>String(t||"").replace(/\\/g,"\\\\").replace(/'/g,"\\'"),je=t=>{const e=String(t||"").trim();return e?e.includes("链接")?e.replace(/链接/g,"图片"):e.endsWith("图片")?`${e}附件`:`${e}图片`:"证件照片图片"},ct=t=>{const e=pt(),s=String((t==null?void 0:t.table_name)||"数据表").trim(),d=String((t==null?void 0:t.sheet_name)||"表格视图1").trim();let u=String((t==null?void 0:t.export_fields)||"").split(",").map(V=>String(V||"").trim()).filter(Boolean);u.length||(u=["order_no","product_name","customer_name","phone"]);let f={};try{f=t!=null&&t.export_column_map?JSON.parse(String(t.export_column_map)):{}}catch(V){f={}}const C={order_no:"订单编号",order_create_time:"订单创建时间",product_name:"产品名称",customer_name:"收货人姓名",phone:"收件号码",idcard:"证件号码",address:"收货地址",id_card_photos:"照片链接（3证/4证）",photo_reupload_count:"照片重新上传次数",production_number:"生产号码",iccid:"ICCID",puk:"PUK"},M={};return u.forEach(V=>{const v=String(f[V]||e[V]||V).trim();v&&(M[v]=C[V]||"")}),JSON.stringify({config_id:Number((t==null?void 0:t.id)||0)||0,event_type:"order_created",trigger_source:"system_push",target:{table_name:s,sheet_name:d},row:M},null,2)},mt=t=>{let e={};try{e=t!=null&&t.export_column_map?JSON.parse(String(t.export_column_map)):{}}catch(f){e={}}const s=String(e.id_card_photos||"证件照片").trim();let d=String((t==null?void 0:t.photo_target_col)||"").trim();d||(d=je(s));const u=String(e.order_no||"订单号").trim();return String.raw`/*
 * 证件照片自动转图片 AirScript
 * 用法：建议单独创建一条自动化任务执行。
 * 触发器可用“新增或者修改记录时”，监听证件照片链接列。
 *
 * 默认列名：
 * - 源列：${te(s)}
 * - 目标列：${te(d)}
 * - 订单号列：${te(u)}
 *
 * 可选脚本入参（动态兼容）：
 * - source_col / 源列
 * - target_col / 目标列
 * - order_col / 订单号列
 * - order_no / 订单号
 */

const DEFAULT_SOURCE_COL = '${te(s)}'
const DEFAULT_TARGET_COL = '${te(d)}'
const DEFAULT_ORDER_COL = '${te(u)}'

function s(v) {
  return v === null || v === undefined ? '' : String(v)
}

function getArg(name, defaults) {
  try {
    if (typeof Arguments !== 'undefined' && Arguments && typeof Arguments.get === 'function') {
      var val = Arguments.get(name, defaults)
      return val === undefined ? defaults : val
    }
  } catch (e) {}
  return defaults
}

function normalizeText(v) {
  return s(v).trim()
}

function getFirstArg(names, defaults) {
  for (var i = 0; i < names.length; i++) {
    var val = getArg(names[i], '')
    if (normalizeText(val) !== '') {
      return val
    }
  }
  return defaults
}

function normalizeUrl(v) {
  var url = s(v).trim()
  if (!url) return ''
  return /^https?:\/\//i.test(url) ? url : ''
}

function splitLinks(raw) {
  var text = s(raw).replace(/\r/g, '\n')
  if (!text.trim()) return []
  var parts = text.split(/\n+|\s+\|\s+|\|/g)
  var out = []
  for (var i = 0; i < parts.length; i++) {
    var url = normalizeUrl(parts[i])
    if (!url) continue
    out.push(url)
  }
  return out
}

function guessFileName(url, index) {
  var clean = s(url).split('?')[0]
  var name = clean.split('/').pop() || ('id-card-' + (index + 1) + '.jpg')
  if (name.indexOf('.') < 0) name += '.jpg'
  return name
}

function getSheet() {
  var sheet = Application.ActiveSheet
  if (!sheet) throw new Error('未获取到当前数据表')
  return sheet
}

function getView(sheet) {
  if (sheet.Views && sheet.Views.ActiveView) return sheet.Views.ActiveView
  if (sheet.Views && typeof sheet.Views.Item === 'function') return sheet.Views.Item(1)
  if (sheet.Views && typeof sheet.Views === 'function') return sheet.Views(1)
  throw new Error('未获取到当前视图')
}

function getRecordCount(view) {
  try {
    if (view.Records && view.Records.Count !== undefined) return Number(view.Records.Count) || 0
  } catch (e) {}
  try {
    if (view.RecordRange && view.RecordRange.Count !== undefined) return Number(view.RecordRange.Count) || 0
  } catch (e2) {}
  throw new Error('无法读取记录数量')
}

function getCellRange(view, rowIndex, fieldName) {
  return view.RecordRange(rowIndex, '@' + fieldName)
}

function getCellText(view, rowIndex, fieldName) {
  try {
    var rg = getCellRange(view, rowIndex, fieldName)
    if (rg.Text !== undefined && rg.Text !== null) return s(rg.Text)
    if (rg.Value !== undefined && rg.Value !== null) return s(rg.Value)
  } catch (e) {}
  return ''
}

function getAttachmentUrls(view, rowIndex, fieldName) {
  try {
    var rg = getCellRange(view, rowIndex, fieldName)
    var value = rg.Value
    if (value && value.Value !== undefined) value = value.Value
    if (!Array.isArray(value)) return []
    var out = []
    for (var i = 0; i < value.length; i++) {
      var item = value[i] || {}
      var url = normalizeUrl(item.LinkUrl || item.linkUrl || item.Url || item.url || item.fileData || item.FileData || '')
      if (url) out.push(url)
    }
    return out
  } catch (e) {
    return []
  }
}

function listEquals(a, b) {
  if (a.length !== b.length) return false
  for (var i = 0; i < a.length; i++) {
    if (a[i] !== b[i]) return false
  }
  return true
}

function buildAttachmentPayload(urls) {
  return urls.map(function(url, index) {
    return { fileName: guessFileName(url, index), fileData: url }
  })
}

function buildAttachmentError(rowIndex, orderNo, targetCol, urls, err) {
  var firstUrl = urls && urls.length ? s(urls[0]) : ""
  var msg = '写入图片字段失败'
  msg += ' | 订单号=' + (s(orderNo) || '未知')
  msg += ' | 行号=' + rowIndex
  msg += ' | 目标列=' + s(targetCol)
  msg += ' | 图片数=' + (urls ? urls.length : 0)
  msg += ' | 首个链接=' + firstUrl
  msg += ' | 原始错误=' + s(err && err.message ? err.message : err)
  msg += ' | 常见原因：图片链接不是直链、源站限制服务端抓取、返回Content-Type异常、图片过大或响应超时'
  return new Error(msg)
}

function httpStatus(resp) {
  if (!resp) return 0
  return Number(resp.status || resp.statusCode || 0) || 0
}

function getHeader(resp, key) {
  if (!resp || !key) return ""
  try {
    if (resp.headers && typeof resp.headers.get === 'function') {
      return s(resp.headers.get(key) || "")
    }
  } catch (e) {}
  try {
    var headers = resp.headers || {}
    return s(headers[key] || headers[key.toLowerCase()] || headers[key.toUpperCase()] || "")
  } catch (e2) {}
  return ""
}

function ensureRemoteImages(urls, rowIndex, orderNo) {
  for (var i = 0; i < urls.length; i++) {
    var url = urls[i]
    var resp = null
    try {
      resp = HTTP.fetch(url, { method: 'GET', timeout: 15000, headers: { 'Accept': 'image/*,*/*' } })
    } catch (fetchErr) {
      throw new Error('图片预检查失败 | 订单号=' + (s(orderNo) || '未知') + ' | 行号=' + rowIndex + ' | 链接=' + url + ' | 原因=脚本环境无法访问该图片地址 | 原始错误=' + s(fetchErr && fetchErr.message ? fetchErr.message : fetchErr))
    }
    var status = httpStatus(resp)
    if (status < 200 || status >= 300) {
      throw new Error('图片预检查失败 | 订单号=' + (s(orderNo) || '未知') + ' | 行号=' + rowIndex + ' | 链接=' + url + ' | HTTP状态=' + status + ' | 原因=图片链接返回非200状态')
    }
    var contentType = normalizeText(getHeader(resp, "content-type"))
    if (contentType && contentType.indexOf("image/") !== 0) {
      throw new Error('图片预检查失败 | 订单号=' + (s(orderNo) || '未知') + ' | 行号=' + rowIndex + ' | 链接=' + url + ' | Content-Type=' + contentType + ' | 原因=返回内容不是图片')
    }
  }
}

function setAttachmentValue(view, rowIndex, fieldName, urls) {
  var rg = getCellRange(view, rowIndex, fieldName)
  if (!urls.length) {
    try { rg.Value = ''; return } catch (e0) {}
    try { rg.Value = Application.DBCellValue([]); return } catch (e1) {}
    try { rg.Value = [[Application.DBCellValue([])]]; return } catch (e2) {}
    try { rg.SetValues([['']]); return } catch (e3) {}
    throw new Error('清空图片字段失败')
  }
  var payload = buildAttachmentPayload(urls)
  try { rg.Value = Application.DBCellValue(payload); return } catch (e1) {}
  try { rg.Value = [[Application.DBCellValue(payload)]]; return } catch (e2) {}
  try { rg.SetValues([[Application.DBCellValue(payload)]]); return } catch (e3) {}
  try { rg.Value = payload; return } catch (e4) {}
  throw new Error('写入图片字段失败')
}

function processOneRow(view, rowIndex, sourceCol, targetCol, orderCol) {
  var orderNo = getCellText(view, rowIndex, orderCol)
  var sourceText = getCellText(view, rowIndex, sourceCol)
  var urls = splitLinks(sourceText)
  var currentUrls = getAttachmentUrls(view, rowIndex, targetCol)
  if (listEquals(urls, currentUrls)) {
    console.log('证件照无变化，跳过: row=' + rowIndex + ', order_no=' + s(orderNo) + ', source_urls=' + JSON.stringify(urls) + ', target_urls=' + JSON.stringify(currentUrls))
    return false
  }
  ensureRemoteImages(urls, rowIndex, orderNo)
  try {
    setAttachmentValue(view, rowIndex, targetCol, urls)
  } catch (err) {
    throw buildAttachmentError(rowIndex, orderNo, targetCol, urls, err)
  }
  console.log('证件照已同步: row=' + rowIndex + ', order_no=' + s(orderNo))
  return true
}

function findRowByOrderNo(view, total, orderCol, orderNo) {
  var target = normalizeText(orderNo)
  if (!target) return 0
  for (var rowIndex = 1; rowIndex <= total; rowIndex++) {
    var currentOrderNo = normalizeText(getCellText(view, rowIndex, orderCol))
    if (currentOrderNo === target) {
      return rowIndex
    }
  }
  return 0
}

function main() {
  var sourceCol = normalizeText(getFirstArg(['source_col', '源列'], DEFAULT_SOURCE_COL)) || DEFAULT_SOURCE_COL
  var targetCol = normalizeText(getFirstArg(['target_col', '目标列'], DEFAULT_TARGET_COL)) || DEFAULT_TARGET_COL
  var orderCol = normalizeText(getFirstArg(['order_col', '订单号列'], DEFAULT_ORDER_COL)) || DEFAULT_ORDER_COL

  var sheet = getSheet()
  var view = getView(sheet)
  var total = getRecordCount(view)
  var triggerOrderNo = normalizeText(getFirstArg(['order_no', '订单号'], ''))
  var updated = 0
  var skipped = 0

  if (triggerOrderNo) {
    var targetRow = findRowByOrderNo(view, total, orderCol, triggerOrderNo)
    if (!targetRow) {
      throw new Error('未找到触发行，订单号=' + triggerOrderNo)
    }
    if (processOneRow(view, targetRow, sourceCol, targetCol, orderCol)) {
      updated = 1
    } else {
      skipped = 1
    }
    console.log('照片转图脚本按触发行执行完成：更新 ' + updated + ' 条，跳过 ' + skipped + ' 条')
    return
  }

  for (var rowIndex = 1; rowIndex <= total; rowIndex++) {
    if (processOneRow(view, rowIndex, sourceCol, targetCol, orderCol)) {
      updated++
    } else {
      skipped++
    }
  }
  console.log('证件照片自动转图片完成：更新 ' + updated + ' 条，跳过 ' + skipped + ' 条')
}

main()
`},we=(t,e)=>{ke.value=t,Re.value=e,de.value=!0},gt=t=>{xe.value=ct(t),we("push","推送示例")},_t=t=>{const e=t,s=qe(t.export_column_map);L.sourceCol=String(s.id_card_photos||"证件照片").trim(),L.targetCol=String(e.photo_target_col||"")||je(L.sourceCol),L.orderCol=String(s.order_no||"订单号").trim(),L.script=mt(t),we("photo","照片转图片示例")},ft=t=>w(null,null,function*(){ve.value=!0,we("callback","回调示例");try{const s=(yield Cl(t.id)).data||{},d=s.field_map||{},u=s.status_cols||{},f=(M,V)=>String(d[M]||V).trim(),C=(M,V)=>String(u[M]||V).trim();I.fieldRows=[{label:"订单号",value:f("order_no",t.sync_order_no_col||"订单号")},{label:"生产号码",value:f("production_number",t.sync_production_number_col||"生产号码")},{label:"ICCID",value:f("iccid",t.sync_iccid_col||"ICCID")},{label:"PUK",value:f("puk",t.sync_puk_col||"PUK")},{label:"快递公司",value:f("express_company",t.sync_express_company_col||"快递公司")},{label:"快递单号",value:f("tracking_number",t.sync_tracking_number_col||"快递单号")},{label:"备注",value:f("remark",t.sync_remark_col||"备注")},{label:"订单状态",value:f("order_status",t.sync_order_status_col||"订单状态")}],I.statusRows=[{label:"回传状态",value:C("status","回传状态")},{label:"回传时间",value:C("time","回传时间")},{label:"回传结果",value:C("result","回传结果")},{label:"回传签名",value:C("signature","回传签名")},{label:"已回传签名",value:C("synced_signature","已回传签名")},{label:"失败签名",value:C("failed_signature","失败签名")}],I.triggerBody=String(s.trigger_body||"").trim(),I.pyScript=String(s.script||"").trim(),I.triggerBody||(I.triggerBody=JSON.stringify({action:"callback_sync",config_id:t.id||0,trigger_source:"system_timer",trigger_time:new Date().toISOString().slice(0,19).replace("T"," ")},null,2)),I.pyScript||(I.pyScript="# 回调脚本生成失败，请稍后重试")}finally{ve.value=!1}}),Ke=Ze({props:{loading:Boolean,rows:{type:Array,required:!0},type:{type:String,required:!0}},emits:["detail","retry"],setup(t,{emit:e}){return()=>N(et,{loading:t.loading,data:t.rows,border:!0,stripe:!0},{default:()=>[N(P,{prop:"order_no",label:"订单号",minWidth:170}),N(P,{prop:"channel_name",label:"渠道",minWidth:130}),t.type==="push"?N(P,{prop:"event_type",label:"事件",width:120},{default:({row:s})=>ze(String(s.event_type||""))}):null,t.type==="push"?N(P,{prop:"trigger_source",label:"来源",width:130},{default:({row:s})=>Me(String(s.trigger_source||""))}):null,N(P,{prop:"status",label:"状态",width:100,align:"center"},{default:({row:s})=>N(cl,{type:s.status==="success"?"success":s.status==="pending"?"warning":"danger"},()=>Be(String(s.status||"")))}),N(P,{prop:"message",label:"结果",minWidth:240,showOverflowTooltip:!0}),N(P,{prop:"created_time",label:"时间",width:170}),N(P,{label:"操作",width:t.type==="push"?130:86,fixed:"right",align:"center"},{default:({row:s})=>N(le,null,()=>[t.type==="push"&&s.status==="failed"?N(_,{link:!0,type:"warning",onClick:()=>e("retry",s)},()=>"重试"):null,N(_,{link:!0,type:"primary",onClick:()=>e("detail",s)},()=>"详情")])})]})}}),yt=()=>w(null,null,function*(){var e,s;const t=yield gl();ae.value=((e=t.data)==null?void 0:e.webhook_url)||"",ee.value=((s=t.data)==null?void 0:s.token)||""}),bt=()=>w(null,null,function*(){var e,s;const t=yield fl();Ie.value=((e=t.data)==null?void 0:e.channels)||[],pe.value=((s=t.data)==null?void 0:s.products)||[]}),z=()=>w(null,null,function*(){H.value=!0;try{const t=yield yl({page:j.page,limit:j.limit});Ne.value=t.data||[],j.total=Number(t.count||0)}finally{H.value=!1}}),vt=()=>w(null,null,function*(){re.value=!0;try{yield _l(ee.value),S.success("保存成功")}finally{re.value=!1}}),ie=t=>w(null,null,function*(){if(!t){S.warning("内容为空");return}yield navigator.clipboard.writeText(t||""),S.success("已复制")}),kt=()=>{Object.assign(i,Q(Q({id:0,export_mode:"channel_product",channel_key:"",product_ids:[],table_name:"",sheet_name:"",push_webhook_url:"",callback_trigger_webhook_url:"",remark:"",export_fields:K.map(t=>t.key),export_col_map:Fe()},Le()),De()))},qe=t=>{try{return t?JSON.parse(t):{}}catch(e){return{}}},xt=t=>t.api_name==="自营"?`self:${t.self_channel_id||0}`:t.api_name?`api:${t.api_name}`:"",He=t=>w(null,null,function*(){if(kt(),t){const e=qe(t.export_column_map),s=t.export_fields?t.export_fields.split(",").filter(Boolean):K.map(d=>d.key);Object.assign(i,{id:t.id,export_mode:t.export_mode||"channel_product",channel_key:xt(t),product_ids:(t.product_ids||[]).map(d=>Number(d)).filter(Boolean),table_name:t.table_name||"",sheet_name:t.sheet_name||"",push_webhook_url:t.push_webhook_url||"",callback_trigger_webhook_url:t.callback_trigger_webhook_url||"",remark:t.remark||"",export_fields:s,export_col_map:K.reduce((d,u)=>{const f=String(e[u.key]||"");return d[u.key]=f||(s.includes(u.key)?u.label:""),d},{})}),ce.forEach(d=>{i[d.key]=String(t[d.key]||"")}),se.forEach(d=>{i[d.key]=String(t[d.key]||"")})}W.value=!0,Pe(()=>{var e;return(e=ne.value)==null?void 0:e.clearValidate()})}),ht=()=>{i.product_ids=[]},Ct=()=>{if(i.export_mode==="all"){i.channel_key="",i.product_ids=[],Pe(()=>{var t;return(t=ne.value)==null?void 0:t.clearValidate(["channel_key","product_ids"])});return}i.export_mode==="channel_only"&&(i.product_ids=[],Pe(()=>{var t;return(t=ne.value)==null?void 0:t.clearValidate(["product_ids"])}))},wt=()=>Q(Q({id:i.id,export_mode:i.export_mode,channel_key:i.channel_key,product_ids:i.product_ids,product_id:i.product_ids[0]||0,table_name:i.table_name,sheet_name:i.sheet_name,push_webhook_url:i.push_webhook_url,callback_trigger_webhook_url:i.callback_trigger_webhook_url,remark:i.remark,export_fields:i.export_fields,export_col_map:JSON.stringify(i.export_fields.reduce((t,e)=>{const s=String(i.export_col_map[e]||"").trim();return s&&(t[e]=s),t},{}))},ce.reduce((t,e)=>(t[e.key]=i[e.key],t),{})),se.reduce((t,e)=>(t[e.key]=i[e.key],t),{})),St=()=>w(null,null,function*(){var e;if(yield(e=ne.value)==null?void 0:e.validate(),i.export_mode!=="all"&&!i.channel_key){S.warning("请选择渠道");return}if(i.export_mode==="channel_product"&&!i.product_ids.length){S.warning("请至少选择一个产品");return}if(!i.export_fields.length){S.warning("请至少选择一个推送字段");return}const t=K.find(s=>i.export_fields.includes(s.key)&&!String(i.export_col_map[s.key]||"").trim());if(t){S.warning(`请先填写${t.label}列标题`);return}oe.value=!0;try{yield bl(wt()),S.success("保存成功"),W.value=!1,yield z()}finally{oe.value=!1}}),Et=t=>w(null,null,function*(){yield Te.confirm(`确定立即推送配置 #${t.id} 的订单数据吗？`,"确认推送",{type:"warning"});const s=(yield xl(t.id)).data||{};S.success(`推送完成：总数 ${s.total||0}，成功 ${s.success_count||0}，失败 ${s.failed_count||0}`)}),Vt=t=>w(null,null,function*(){yield Te.confirm(`确定立即执行配置 #${t.id} 的回调同步吗？`,"确认回调",{type:"warning"});const e=yield hl(t.id);S.success(e.msg||"执行完成")}),Pt=t=>w(null,null,function*(){he.value=t,Z.value=!0,ye.value=!0,b.enabled=!1,b.interval=5,b.batchSize=50,b.lastTime="",b.lastResult="";try{const s=(yield wl(t.id)).data||{};b.enabled=Number(s.callback_cron_enabled||0)===1,b.interval=Math.max(1,Number(s.callback_cron_interval||5)),b.batchSize=Math.max(1,Number(s.callback_cron_batch_size||50)),b.lastTime=String(s.callback_cron_last_time||""),b.lastResult=String(s.callback_cron_last_result||"")}finally{ye.value=!1}}),Tt=()=>w(null,null,function*(){const t=he.value;if(t){if(b.enabled&&!t.callback_trigger_webhook_url){S.warning("请先在编辑配置里填写回调Webhook");return}be.value=!0;try{yield Sl({id:t.id,callback_cron_enabled:b.enabled?1:0,callback_cron_interval:b.interval,callback_cron_batch_size:b.batchSize}),S.success("回调设置已保存"),Z.value=!1,yield z()}finally{be.value=!1}}}),Rt=t=>w(null,null,function*(){yield vl(t.id),S.success("复制成功"),yield z()}),It=t=>w(null,null,function*(){yield Te.confirm(`确定删除云导出配置 #${t.id} 吗？`,"确认删除",{type:"warning"}),yield kl(t.id),S.success("删除成功"),yield z()}),G=()=>w(null,null,function*(){Y.value=!0;try{const t={page:D.page,limit:D.limit,order_no:$.order_no||"",status:$.status||""};if(h.value==="push"){const e=yield El(t);Ue.value=e.data||[],D.total=Number(e.count||0)}else{const e=yield Vl(t);Ae.value=e.data||[],D.total=Number(e.count||0)}}finally{Y.value=!1}}),Se=()=>{D.page=1,G()},Nt=()=>{$.order_no="",$.status="",Se()},Ge=t=>{Oe.value=t,U.value=!0},Ut=t=>w(null,null,function*(){const e=yield Pl(t.id);Number(e.code)===1?S.success(e.msg||"重试成功"):S.error(e.msg||"重试失败"),yield G()}),At=t=>{t==="history"?G():z()};return Jt(()=>w(null,null,function*(){yield Promise.all([yt(),bt(),z(),G()])})),(t,e)=>{const s=nl,d=sl,u=al,f=ol,C=rl,M=ll,V=Yt,v=ul,F=tl,me=el,Ee=il,Qe=Zt,Ot=Xt,Wt=Qt,Lt=Gt,ge=Ht,Dt=dl,Ft=pl,ue=jt("ripple"),Ve=Kt;return c(),y("div",Tl,[o(Qe,{modelValue:l(E),"onUpdate:modelValue":e[11]||(e[11]=r=>B(E)?E.value=r:null),onTabChange:At},{default:a(()=>[o(V,{label:"导出配置",name:"config"},{default:a(()=>[o(C,{shadow:"never",class:"config-card"},{header:a(()=>[n("div",Rl,[e[35]||(e[35]=n("div",null,[n("span",null,"接收配置"),n("p",null,"表格回调地址和 Token，供云文档脚本回传订单状态。")],-1)),q((c(),A(l(_),{type:"primary",loading:l(re),onClick:vt},{icon:a(()=>[o(s,{icon:"ri:save-line"})]),default:a(()=>[e[34]||(e[34]=m(" 保存 Token ",-1))]),_:1},8,["loading"])),[[ue]])])]),default:a(()=>[o(f,{gutter:12},{default:a(()=>[o(u,{xs:24,lg:16},{default:a(()=>[o(d,{modelValue:l(ae),"onUpdate:modelValue":e[1]||(e[1]=r=>B(ae)?ae.value=r:null),readonly:""},{prepend:a(()=>[...e[36]||(e[36]=[m("Webhook",-1)])]),append:a(()=>[o(l(_),{onClick:e[0]||(e[0]=r=>ie(l(ae)))},{default:a(()=>[...e[37]||(e[37]=[m("复制",-1)])]),_:1})]),_:1},8,["modelValue"])]),_:1}),o(u,{xs:24,lg:8},{default:a(()=>[o(d,{modelValue:l(ee),"onUpdate:modelValue":e[2]||(e[2]=r=>B(ee)?ee.value=r:null),modelModifiers:{trim:!0},"show-password":"",placeholder:"回调 Token"},{prepend:a(()=>[...e[38]||(e[38]=[m("Token",-1)])]),append:a(()=>[o(l(_),{onClick:ut},{default:a(()=>[...e[39]||(e[39]=[m("随机",-1)])]),_:1})]),_:1},8,["modelValue"])]),_:1})]),_:1})]),_:1}),o(C,{shadow:"never"},{header:a(()=>[n("div",Il,[e[42]||(e[42]=n("div",null,[n("span",null,"任务配置"),n("p",null,"按渠道或渠道+产品建立云文档推送规则。")],-1)),o(l(le),{wrap:""},{default:a(()=>[q((c(),A(l(_),{type:"primary",onClick:e[3]||(e[3]=r=>He())},{icon:a(()=>[o(s,{icon:"ri:add-line"})]),default:a(()=>[e[40]||(e[40]=m(" 新增配置 ",-1))]),_:1})),[[ue]]),q((c(),A(l(_),{loading:l(H),onClick:z},{icon:a(()=>[o(s,{icon:"ri:refresh-line"})]),default:a(()=>[e[41]||(e[41]=m(" 刷新 ",-1))]),_:1},8,["loading"])),[[ue]])]),_:1})])]),default:a(()=>[q((c(),A(l(et),{data:l(Ne),"row-key":"id",border:"",stripe:""},{default:a(()=>[o(l(P),{prop:"id",label:"ID",width:"78"}),o(l(P),{prop:"export_mode_text",label:"方式",width:"110"}),o(l(P),{prop:"channel_name",label:"产品渠道","min-width":"150"}),o(l(P),{label:"产品","min-width":"240"},{default:a(({row:r})=>[n("div",Nl,[n("div",Ul,x(Ce(r)[0]||"-"),1),Ce(r).length>1?(c(),A(l(_),{key:0,link:"",type:"primary",onClick:k=>it(r)},{default:a(()=>[...e[43]||(e[43]=[m(" 查看 ",-1)])]),_:1},8,["onClick"])):fe("",!0)])]),_:1}),o(l(P),{prop:"remark",label:"备注","min-width":"160"},{default:a(({row:r})=>[m(x(r.remark||"-"),1)]),_:1}),o(l(P),{label:"推送/回调",width:"210",align:"center"},{default:a(({row:r})=>[o(l(le),{wrap:""},{default:a(()=>[o(l(_),{link:"",type:"warning",onClick:k=>Et(r)},{default:a(()=>[...e[44]||(e[44]=[m("推送",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"primary",onClick:k=>Vt(r)},{default:a(()=>[...e[45]||(e[45]=[m("回调",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"primary",onClick:k=>Pt(r)},{default:a(()=>[...e[46]||(e[46]=[m("回调设置",-1)])]),_:1},8,["onClick"])]),_:2},1024)]),_:1}),o(l(P),{label:"自动化脚本代码",width:"220",align:"center"},{default:a(({row:r})=>[o(l(le),{wrap:""},{default:a(()=>[o(l(_),{link:"",type:"danger",onClick:k=>gt(r)},{default:a(()=>[...e[47]||(e[47]=[m("推送示例",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"primary",onClick:k=>ft(r)},{default:a(()=>[...e[48]||(e[48]=[m("回调示例",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"primary",onClick:k=>_t(r)},{default:a(()=>[...e[49]||(e[49]=[m("照片转图片示例",-1)])]),_:1},8,["onClick"])]),_:2},1024)]),_:1}),o(l(P),{label:"操作",width:"210",fixed:"right",align:"center"},{default:a(({row:r})=>[o(l(_),{link:"",type:"primary",onClick:k=>He(r)},{default:a(()=>[...e[50]||(e[50]=[m("编辑",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"primary",onClick:k=>Rt(r)},{default:a(()=>[...e[51]||(e[51]=[m("复制",-1)])]),_:1},8,["onClick"]),o(l(_),{link:"",type:"danger",onClick:k=>It(r)},{default:a(()=>[...e[52]||(e[52]=[m("删除",-1)])]),_:1},8,["onClick"])]),_:1})]),_:1},8,["data"])),[[Ve,l(H)]]),n("div",Al,[o(M,{"current-page":l(j).page,"onUpdate:currentPage":e[4]||(e[4]=r=>l(j).page=r),"page-size":l(j).limit,"onUpdate:pageSize":e[5]||(e[5]=r=>l(j).limit=r),total:l(j).total,"page-sizes":[20,50,100],layout:"total, sizes, prev, pager, next, jumper",onSizeChange:z,onCurrentChange:z},null,8,["current-page","page-size","total"])])]),_:1})]),_:1}),o(V,{label:"推送历史",name:"history"},{default:a(()=>[o(C,{shadow:"never"},{default:a(()=>[o(Ee,{model:l($),"label-width":"76px",class:"search-form"},{default:a(()=>[o(f,{gutter:12},{default:a(()=>[o(u,{xs:24,lg:7},{default:a(()=>[o(v,{label:"订单号"},{default:a(()=>[o(d,{modelValue:l($).order_no,"onUpdate:modelValue":e[6]||(e[6]=r=>l($).order_no=r),modelModifiers:{trim:!0},clearable:"",placeholder:"订单号",onKeyup:qt(Se,["enter"])},null,8,["modelValue"])]),_:1})]),_:1}),o(u,{xs:24,lg:5},{default:a(()=>[o(v,{label:"状态"},{default:a(()=>[o(me,{modelValue:l($).status,"onUpdate:modelValue":e[7]||(e[7]=r=>l($).status=r),clearable:"",placeholder:"全部状态"},{default:a(()=>[o(F,{label:"成功",value:"success"}),o(F,{label:"失败",value:"failed"})]),_:1},8,["modelValue"])]),_:1})]),_:1}),o(u,{xs:24,lg:8},{default:a(()=>[o(v,{class:"search-actions"},{default:a(()=>[o(l(le),{wrap:""},{default:a(()=>[q((c(),A(l(_),{type:"primary",onClick:Se},{icon:a(()=>[o(s,{icon:"ri:search-line"})]),default:a(()=>[e[53]||(e[53]=m(" 查询 ",-1))]),_:1})),[[ue]]),q((c(),A(l(_),{onClick:Nt},{icon:a(()=>[o(s,{icon:"ri:refresh-line"})]),default:a(()=>[e[54]||(e[54]=m(" 重置 ",-1))]),_:1})),[[ue]])]),_:1})]),_:1})]),_:1})]),_:1})]),_:1},8,["model"]),o(Qe,{modelValue:l(h),"onUpdate:modelValue":e[8]||(e[8]=r=>B(h)?h.value=r:null),onTabChange:G},{default:a(()=>[o(V,{label:"推送历史",name:"push"},{default:a(()=>[o(l(Ke),{loading:l(Y),rows:l(Ue),type:"push",onDetail:Ge,onRetry:Ut},null,8,["loading","rows"])]),_:1}),o(V,{label:"表格回调历史",name:"callback"},{default:a(()=>[o(l(Ke),{loading:l(Y),rows:l(Ae),type:"callback",onDetail:Ge},null,8,["loading","rows"])]),_:1})]),_:1},8,["modelValue"]),n("div",Ol,[o(M,{"current-page":l(D).page,"onUpdate:currentPage":e[9]||(e[9]=r=>l(D).page=r),"page-size":l(D).limit,"onUpdate:pageSize":e[10]||(e[10]=r=>l(D).limit=r),total:l(D).total,"page-sizes":[20,50,100],layout:"total, sizes, prev, pager, next, jumper",onSizeChange:G,onCurrentChange:G},null,8,["current-page","page-size","total"])])]),_:1})]),_:1})]),_:1},8,["modelValue"]),o(Lt,{modelValue:l(W),"onUpdate:modelValue":e[21]||(e[21]=r=>B(W)?W.value=r:null),title:l(i).id?"编辑配置":"新增配置",size:"860px","destroy-on-close":""},{footer:a(()=>[o(l(_),{onClick:e[20]||(e[20]=r=>W.value=!1)},{default:a(()=>[...e[62]||(e[62]=[m("取消",-1)])]),_:1}),o(l(_),{type:"primary",loading:l(oe),onClick:St},{default:a(()=>[...e[63]||(e[63]=[m("保存",-1)])]),_:1},8,["loading"])]),default:a(()=>[o(Ee,{ref_key:"editFormRef",ref:ne,model:l(i),rules:lt,"label-width":"112px"},{default:a(()=>[o(Ot,{"content-position":"left"},{default:a(()=>[...e[55]||(e[55]=[m("基础配置",-1)])]),_:1}),o(f,{gutter:12},{default:a(()=>[o(u,{xs:24,lg:12},{default:a(()=>[o(v,{label:"导出方式",prop:"export_mode"},{default:a(()=>[o(Wt,{modelValue:l(i).export_mode,"onUpdate:modelValue":e[12]||(e[12]=r=>l(i).export_mode=r),options:[{label:"全部",value:"all"},{label:"渠道+产品",value:"channel_product"},{label:"渠道",value:"channel_only"}],onChange:Ct},null,8,["modelValue"])]),_:1})]),_:1}),l(i).export_mode!=="all"?(c(),A(u,{key:0,xs:24,lg:12},{default:a(()=>[o(v,{label:"渠道",prop:"channel_key"},{default:a(()=>[o(me,{modelValue:l(i).channel_key,"onUpdate:modelValue":e[13]||(e[13]=r=>l(i).channel_key=r),filterable:"",placeholder:"选择渠道",onChange:ht},{default:a(()=>[(c(!0),y(T,null,O(l(Ie),r=>(c(),A(F,{key:r.key,label:r.name,value:r.key},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})):fe("",!0),l(i).export_mode==="channel_product"?(c(),A(u,{key:1,xs:24},{default:a(()=>[o(v,{label:"产品",prop:"product_ids"},{default:a(()=>[o(me,{modelValue:l(i).product_ids,"onUpdate:modelValue":e[14]||(e[14]=r=>l(i).product_ids=r),multiple:"",filterable:"","collapse-tags":"","collapse-tags-tooltip":"",placeholder:"选择产品"},{default:a(()=>[(c(!0),y(T,null,O(l(rt),r=>(c(),A(F,{key:r.id,label:r.name,value:r.id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})):fe("",!0),o(u,{xs:24,lg:12},{default:a(()=>[o(v,{label:"表格名称",prop:"table_name"},{default:a(()=>[o(d,{modelValue:l(i).table_name,"onUpdate:modelValue":e[15]||(e[15]=r=>l(i).table_name=r),modelModifiers:{trim:!0},placeholder:"多维表格名称"},null,8,["modelValue"])]),_:1})]),_:1}),o(u,{xs:24,lg:12},{default:a(()=>[o(v,{label:"Sheet名称",prop:"sheet_name"},{default:a(()=>[o(d,{modelValue:l(i).sheet_name,"onUpdate:modelValue":e[16]||(e[16]=r=>l(i).sheet_name=r),modelModifiers:{trim:!0},placeholder:"Sheet名称"},null,8,["modelValue"])]),_:1})]),_:1}),o(u,{xs:24},{default:a(()=>[o(v,{label:"备注"},{default:a(()=>[o(d,{modelValue:l(i).remark,"onUpdate:modelValue":e[17]||(e[17]=r=>l(i).remark=r),type:"textarea",rows:2,maxlength:"300","show-word-limit":""},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),n("section",Wl,[e[56]||(e[56]=n("div",{class:"config-section-title"},"推送数据到WPS",-1)),e[57]||(e[57]=n("div",{class:"config-tip danger-tip"},"输入框中的标题必须和 WPS 表格列标题一致；如 WPS 表格标题修改，这里和 WPS 自动化流程需要同步修改。",-1)),o(v,{label:"推送Webhook"},{default:a(()=>[o(d,{modelValue:l(i).push_webhook_url,"onUpdate:modelValue":e[18]||(e[18]=r=>l(i).push_webhook_url=r),modelModifiers:{trim:!0},placeholder:"WPS 自动化 webhook 触发地址"},null,8,["modelValue"])]),_:1}),o(f,{gutter:12,class:"field-grid"},{default:a(()=>[(c(),y(T,null,O(K,r=>o(u,{key:r.key,xs:24,lg:12},{default:a(()=>[o(v,{label:r.label},{default:a(()=>[o(d,{modelValue:l(i).export_col_map[r.key],"onUpdate:modelValue":k=>l(i).export_col_map[r.key]=k,modelModifiers:{trim:!0},placeholder:r.placeholder},{append:a(()=>[o(l(_),{type:Je(r.key)?"primary":"",onClick:k=>dt(r)},{default:a(()=>[m(x(Je(r.key)?"已启用":"启用"),1)]),_:2},1032,["type","onClick"])]),_:2},1032,["modelValue","onUpdate:modelValue","placeholder"])]),_:2},1032,["label"])]),_:2},1024)),64))]),_:1})]),n("section",Ll,[e[60]||(e[60]=n("div",{class:"config-section-title"},"接收WPS回调数据",-1)),e[61]||(e[61]=n("div",{class:"config-tip danger-tip"},"输入框中的标题必须和 WPS 表格列标题一致；如 WPS 表格标题修改，这里和 WPS 自动化流程需要同步修改。",-1)),o(v,{label:"回调Webhook"},{default:a(()=>[o(d,{modelValue:l(i).callback_trigger_webhook_url,"onUpdate:modelValue":e[19]||(e[19]=r=>l(i).callback_trigger_webhook_url=r),modelModifiers:{trim:!0},placeholder:"WPS 回调自动化 webhook 触发地址"},null,8,["modelValue"])]),_:1}),o(f,{gutter:12},{default:a(()=>[(c(),y(T,null,O(ce,r=>o(u,{key:r.key,xs:24,lg:12},{default:a(()=>[o(v,{label:r.label},{default:a(()=>[o(d,{modelValue:l(i)[r.key],"onUpdate:modelValue":k=>l(i)[r.key]=k,modelModifiers:{trim:!0},placeholder:r.placeholder},null,8,["modelValue","onUpdate:modelValue","placeholder"])]),_:2},1032,["label"])]),_:2},1024)),64))]),_:1}),l(i).sync_order_status_col?(c(),y(T,{key:0},[e[58]||(e[58]=n("div",{class:"status-map-title"},"订单状态映射",-1)),e[59]||(e[59]=n("div",{class:"config-tip status-map-tip"},"WPS 回调的订单状态值如果不是系统默认状态名称，可在这里填写映射值；多个值用逗号分隔。",-1)),n("table",Dl,[n("tbody",null,[(c(!0),y(T,null,O(l(tt),r=>(c(),y("tr",{key:r.map(k=>k.key).join("_")},[(c(!0),y(T,null,O(r,k=>(c(),y(T,{key:k.key},[n("th",null,x(k.label),1),n("td",null,[o(d,{modelValue:l(i)[k.key],"onUpdate:modelValue":$t=>l(i)[k.key]=$t,modelModifiers:{trim:!0},placeholder:"多个值用逗号分隔"},null,8,["modelValue","onUpdate:modelValue"])])],64))),128))]))),128))])])],64)):fe("",!0)])]),_:1},8,["model"])]),_:1},8,["modelValue","title"]),o(ge,{modelValue:l(U),"onUpdate:modelValue":e[22]||(e[22]=r=>B(U)?U.value=r:null),title:"日志详情",width:"920px"},{default:a(()=>[n("div",Fl,[n("table",$l,[n("tbody",null,[(c(!0),y(T,null,O(l(nt),r=>(c(),y("tr",{key:r.key},[n("th",null,x(r.label),1),n("td",null,x(r.value||"-"),1)]))),128))])]),(c(!0),y(T,null,O(l(st),r=>(c(),y("div",{key:r.key,class:"log-json-section"},[n("div",zl,x(r.label),1),n("pre",Ml,x(r.value||"-"),1)]))),128))])]),_:1},8,["modelValue"]),o(ge,{modelValue:l(J),"onUpdate:modelValue":e[23]||(e[23]=r=>B(J)?J.value=r:null),title:"全部产品",width:"560px"},{default:a(()=>[n("div",Bl,[(c(!0),y(T,null,O(l(We),r=>(c(),y("div",{key:r,class:"all-product-item"},x(r),1))),128))])]),_:1},8,["modelValue"]),o(ge,{modelValue:l(Z),"onUpdate:modelValue":e[28]||(e[28]=r=>B(Z)?Z.value=r:null),title:"回调设置",width:"640px","destroy-on-close":""},{footer:a(()=>[o(l(_),{onClick:e[27]||(e[27]=r=>Z.value=!1)},{default:a(()=>[...e[66]||(e[66]=[m("取消",-1)])]),_:1}),o(l(_),{type:"primary",loading:l(be),onClick:Tt},{default:a(()=>[...e[67]||(e[67]=[m("保存",-1)])]),_:1},8,["loading"])]),default:a(()=>[q((c(),A(Ee,{model:l(b),"label-width":"112px",class:"callback-cron-form"},{default:a(()=>[o(v,{label:"启用任务"},{default:a(()=>[o(Dt,{modelValue:l(b).enabled,"onUpdate:modelValue":e[24]||(e[24]=r=>l(b).enabled=r),"active-text":"开启","inactive-text":"关闭"},null,8,["modelValue"]),e[64]||(e[64]=n("div",{class:"form-tip"},"开启后，系统统一定时任务会按频率触发 WPS 的补偿回传 Webhook。",-1))]),_:1}),o(v,{label:"执行频率"},{default:a(()=>[o(me,{modelValue:l(b).interval,"onUpdate:modelValue":e[25]||(e[25]=r=>l(b).interval=r),placeholder:"选择频率"},{default:a(()=>[o(F,{label:"每1分钟",value:1}),o(F,{label:"每5分钟",value:5}),o(F,{label:"每10分钟",value:10}),o(F,{label:"每15分钟",value:15}),o(F,{label:"每30分钟",value:30}),o(F,{label:"每1小时",value:60})]),_:1},8,["modelValue"])]),_:1}),o(v,{label:"每批数量"},{default:a(()=>[o(Ft,{modelValue:l(b).batchSize,"onUpdate:modelValue":e[26]||(e[26]=r=>l(b).batchSize=r),min:1,max:500,step:10,"controls-position":"right"},null,8,["modelValue"]),e[65]||(e[65]=n("div",{class:"form-tip"},"单次触发最多处理的记录数，建议 50 左右，数据量大时可适当调高。",-1))]),_:1}),o(v,{label:"回调Webhook"},{default:a(()=>{var r;return[o(d,{"model-value":((r=l(he))==null?void 0:r.callback_trigger_webhook_url)||"",readonly:"",placeholder:"请先在编辑配置里填写回调Webhook"},null,8,["model-value"])]}),_:1}),o(v,{label:"最后执行"},{default:a(()=>[n("div",Jl,x(l(b).lastTime||"从未执行"),1)]),_:1}),o(v,{label:"执行结果"},{default:a(()=>[o(d,{"model-value":l(b).lastResult||"暂无",type:"textarea",rows:3,readonly:""},null,8,["model-value"])]),_:1})]),_:1},8,["model"])),[[Ve,l(ye)]])]),_:1},8,["modelValue"]),o(ge,{modelValue:l(de),"onUpdate:modelValue":e[33]||(e[33]=r=>B(de)?de.value=r:null),title:l(Re),width:"860px",class:"cloudexport-example-dialog","destroy-on-close":""},{default:a(()=>[q((c(),y("div",jl,[l(ke)==="push"?(c(),y(T,{key:0},[e[72]||(e[72]=n("div",{class:"example-section"},[n("div",{class:"example-title"},"第1步：在 WPS 新建 webhook 触发"),n("div",{class:"example-desc"},"进入当前多维表自动化，新建一条自动化，触发器选择 webhook 触发。保存后，把 WPS 生成的回调地址填到系统的推送 Webhook 地址里。")],-1)),n("div",Kl,[e[69]||(e[69]=n("div",{class:"example-title"},"第2步：给 webhook 增加请求体，并创建分支",-1)),e[70]||(e[70]=n("div",{class:"example-desc"},"下面是系统推送到 WPS 的 JSON 示例。目标表和视图名称要与 WPS 里的实际名称一致。",-1)),e[71]||(e[71]=n("div",{class:"example-copy-label"},"推送请求体示例",-1)),n("pre",ql,x(l(xe)),1),o(l(le),{wrap:""},{default:a(()=>[o(l(_),{type:"primary",onClick:e[29]||(e[29]=r=>ie(l(xe)))},{default:a(()=>[...e[68]||(e[68]=[m("复制请求体示例",-1)])]),_:1})]),_:1})])],64)):l(ke)==="photo"?(c(),y("div",Hl,[e[82]||(e[82]=n("div",{class:"example-title"},"照片转图片示例",-1)),e[83]||(e[83]=n("div",{class:"example-desc"},"这段脚本只处理当前多维表，不会请求后台。建议单独配置成一条自动化任务，监听证件照片链接列的新增或修改。",-1)),n("table",Gl,[e[79]||(e[79]=n("tr",null,[n("th",{style:{width:"220px"}},"项目"),n("th",null,"说明")],-1)),n("tr",null,[e[73]||(e[73]=n("td",null,"链接源列",-1)),n("td",null,[n("code",null,x(l(L).sourceCol),1)])]),n("tr",null,[e[74]||(e[74]=n("td",null,"订单号列",-1)),n("td",null,[n("code",null,x(l(L).orderCol),1)])]),n("tr",null,[e[78]||(e[78]=n("td",null,"图片目标列",-1)),n("td",null,[e[75]||(e[75]=m("请先在多维表里新增一个 ",-1)),e[76]||(e[76]=n("code",null,"图片和附件",-1)),e[77]||(e[77]=m(" 字段，字段名填 ",-1)),n("code",null,x(l(L).targetCol),1)])]),e[80]||(e[80]=n("tr",null,[n("td",null,"执行方式"),n("td",null,[m("触发器选 "),n("code",null,"新增或者修改记录时"),m("，动作选 "),n("code",null,"执行AirScript脚本"),m("。如要只处理当前变动行，请给脚本动作增加参数 "),n("code",null,"order_no"),m("，值绑定当前行的订单号变量。")])],-1))]),e[84]||(e[84]=n("div",{class:"example-copy-label"},"AirScript",-1)),n("pre",Ql,x(l(L).script),1),o(l(_),{type:"primary",onClick:e[30]||(e[30]=r=>ie(l(L).script))},{default:a(()=>[...e[81]||(e[81]=[m("复制脚本",-1)])]),_:1})])):(c(),y(T,{key:2},[n("div",Xl,[e[87]||(e[87]=n("div",{class:"example-title"},"一、回传字段",-1)),e[88]||(e[88]=n("div",{class:"example-desc"},"当前配置会回传给系统的业务字段如下。补偿任务会把这些列逐条 POST 回系统，供后端按 config_id 解析。",-1)),n("table",Yl,[e[85]||(e[85]=n("tr",null,[n("th",null,"字段"),n("th",null,"列名")],-1)),(c(!0),y(T,null,O(l(I).fieldRows,r=>(c(),y("tr",{key:r.label},[n("td",null,x(r.label),1),n("td",null,[n("code",null,x(r.value),1)])]))),128))]),e[89]||(e[89]=n("div",{class:"example-copy-label"},"回传状态字段",-1)),n("table",Zl,[e[86]||(e[86]=n("tr",null,[n("th",null,"字段"),n("th",null,"列名")],-1)),(c(!0),y(T,null,O(l(I).statusRows,r=>(c(),y("tr",{key:r.label},[n("td",null,x(r.label),1),n("td",null,[n("code",null,x(r.value),1)])]))),128))])]),n("div",er,[e[91]||(e[91]=n("div",{class:"example-title"},"二、系统触发 WPS 的 Webhook 请求体",-1)),e[92]||(e[92]=n("div",{class:"example-copy-label"},"请求体示例",-1)),n("pre",tr,x(l(I).triggerBody),1),o(l(_),{onClick:e[31]||(e[31]=r=>ie(l(I).triggerBody))},{default:a(()=>[...e[90]||(e[90]=[m("复制触发请求体",-1)])]),_:1})]),n("div",lr,[e[94]||(e[94]=n("div",{class:"example-title"},"三、Python 回调脚本",-1)),e[95]||(e[95]=n("div",{class:"example-copy-label"},"脚本",-1)),n("pre",rr,x(l(I).pyScript),1),o(l(_),{type:"primary",onClick:e[32]||(e[32]=r=>ie(l(I).pyScript))},{default:a(()=>[...e[93]||(e[93]=[m("复制 Python 脚本",-1)])]),_:1})])],64))])),[[Ve,l(ve)]])]),_:1},8,["modelValue","title"])])}}}),ko=ml(or,[["__scopeId","data-v-d82a55b7"]]);export{ko as default};
