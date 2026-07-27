var qt=Object.defineProperty;var tt=Object.getOwnPropertySymbols;var Jt=Object.prototype.hasOwnProperty,Ht=Object.prototype.propertyIsEnumerable;var lt=(m,V,x)=>V in m?qt(m,V,{enumerable:!0,configurable:!0,writable:!0,value:x}):m[V]=x,X=(m,V)=>{for(var x in V||(V={}))Jt.call(V,x)&&lt(m,x,V[x]);if(tt)for(var x of tt(V))Ht.call(V,x)&&lt(m,x,V[x]);return m};var C=(m,V,x)=>new Promise((oe,G)=>{var Z=D=>{try{B(x.next(D))}catch(K){G(K)}},ne=D=>{try{B(x.throw(D))}catch(K){G(K)}},B=D=>D.done?oe(D.value):Promise.resolve(D.value).then(Z,ne);B((x=x.apply(m,V)).next())});import{Q as Gt,T as Qt,U as Xt,V as Yt,i as Zt,j as f,a1 as rt,a2 as T,a0 as ae,v as el,a3 as tl,g as ll,h as rl,M as al,L as ol,R as nl,a4 as sl,x as il,N as ul,f as dl,X as pl,O as cl,E as S,w as ml,u as Te}from"./element-plus-VqaXtxk-.js";import{_ as gl}from"./index.vue_vue_type_script_setup_true_lang-BEnScwYd.js";import{d as at,r as Y,o as _l,au as fl,P as b,Q as c,a4 as a,_ as o,i as z,a as y,u as l,a2 as p,S as n,$ as H,R as L,a0 as _e,a3 as v,aB as yl,H as E,ak as O,c as Ae,h as N,n as Ue}from"./vue-vendor-DREBtWNI.js";import{a as A}from"./index-CH4kbkb9.js";import{_ as bl}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./crypto-js-H8jJBLUp.js";import"./iconify-DIzUjaMv.js";import"./axios-CmxDtsPj.js";import"./app-utils-vendor-bV2yjR3u.js";import"./nprogress-BN-w8n2a.js";function vl(){return A.get({url:"/admin/cloudexport/getConfig",successCodes:[1],rawResponse:!0})}function kl(m){return A.post({url:"/admin/cloudexport/saveConfig",params:{token:m},successCodes:[1],rawResponse:!0})}function hl(){return A.get({url:"/admin/cloudexport/getOptions",successCodes:[1],rawResponse:!0})}function xl(m){return A.get({url:"/admin/cloudexport/getList",params:m,successCodes:[1],rawResponse:!0})}function Cl(m){return A.post({url:"/admin/cloudexport/saveItem",params:m,successCodes:[1],rawResponse:!0})}function wl(m){return A.post({url:"/admin/cloudexport/copyItem",params:{id:m},successCodes:[1],rawResponse:!0})}function Sl(m){return A.post({url:"/admin/cloudexport/deleteItem",params:{id:m},successCodes:[1],rawResponse:!0})}function El(m){return A.post({url:"/admin/cloudexport/triggerConfigPush",params:{config_id:m},successCodes:[1],rawResponse:!0,timeout:6e5})}function Vl(m){return A.post({url:"/admin/cloudexport/triggerCallbackSync",params:{config_id:m},successCodes:[1],rawResponse:!0,timeout:6e5})}function Tl(m){return A.get({url:"/admin/cloudexport/getCallbackPyScript",params:{id:m},successCodes:[1],rawResponse:!0})}function Al(m){return A.get({url:"/admin/cloudexport/getCallbackCronConfig",params:{id:m},successCodes:[1],rawResponse:!0})}function Ul(m){return A.post({url:"/admin/cloudexport/saveCallbackCronConfig",params:m,successCodes:[1],rawResponse:!0})}function Pl(m){return A.get({url:"/admin/cloudexport/getPushLogs",params:m,successCodes:[1],rawResponse:!0})}function Rl(m){return A.get({url:"/admin/cloudexport/getCallbackLogs",params:m,successCodes:[1],rawResponse:!0})}function Il(m){return A.post({url:"/admin/cloudexport/retryPushLog",params:{id:m},successCodes:[0,1],rawResponse:!0})}const Nl={class:"admin-cloud-export-page"},Dl={class:"card-header"},Ll={class:"card-header"},Ol={class:"product-list"},Wl={class:"product-text"},Bl={class:"pagination-wrap"},$l={class:"pagination-wrap"},Fl={class:"config-section-panel"},jl={class:"config-section-panel"},Ml={class:"status-map-title"},zl={class:"config-tip status-map-tip"},Kl={class:"status-map-table"},ql={class:"log-detail"},Jl={class:"log-detail-table"},Hl={class:"log-json-title"},Gl={class:"json-preview"},Ql={class:"all-product-list"},Xl={class:"cron-readonly-text"},Yl={class:"example-dialog"},Zl={class:"example-section"},er={class:"example-code"},tr={key:1,class:"example-section"},lr={class:"guide-grid"},rr={class:"example-code example-code-large"},ar={class:"example-section"},or={class:"guide-grid"},nr={class:"guide-grid"},sr={class:"example-section"},ir={class:"example-code"},ur={class:"example-section"},dr={class:"example-code example-code-large"},pr=at({name:"AdminCloudExportIndex",__name:"index",setup(m){const V=y("config"),x=y("push"),oe=y(!1),G=y(!1),Z=y(!1),ne=y(!1),B=y(!1),D=y(!1),K=y(!1),ee=y(!1),fe=y(!1),ye=y(!1),de=y(!1),be=y(!1),ve=y("push"),Pe=y(""),te=y(""),le=y(""),ke=y(""),Re=y([]),pe=y([]),Ie=y([]),Ne=y([]),De=y([]),Le=y(null),Oe=y([]),he=y(null),se=y(),W=Y({sourceCol:"",targetCol:"",orderCol:"",script:""}),U=Y({triggerBody:"",pyScript:"",fieldRows:[],statusRows:[]}),k=Y({enabled:!1,interval:5,batchSize:50,lastTime:"",lastResult:""}),q=Y({page:1,limit:20,total:0}),$=Y({page:1,limit:20,total:0}),j=Y({order_no:"",status:""}),J=[{key:"order_no",label:"订单号",placeholder:"WPS表中订单号对应列名"},{key:"order_create_time",label:"订单创建时间",placeholder:"WPS表中订单创建时间对应列名"},{key:"product_name",label:"产品名称",placeholder:"WPS表中产品名称对应列名"},{key:"customer_name",label:"姓名",placeholder:"WPS表中姓名对应列名"},{key:"phone",label:"电话",placeholder:"WPS表中电话对应列名"},{key:"idcard",label:"证件号",placeholder:"WPS表中证件号对应列名"},{key:"address",label:"地址",placeholder:"WPS表中地址对应列名"},{key:"id_card_photos",label:"证件照片",placeholder:"WPS表中证件照片对应列名"},{key:"photo_reupload_count",label:"照片重传次数",placeholder:"WPS表中照片重传次数对应列名"},{key:"custom_order_fields",label:"自定义字段",placeholder:"WPS表中自定义字段对应列名"},{key:"production_number",label:"生产号码",placeholder:"WPS表中生产号码对应列名"},{key:"iccid",label:"ICCID",placeholder:"WPS表中ICCID对应列名"},{key:"puk",label:"PUK",placeholder:"WPS表中PUK对应列名"},{key:"refund_status",label:"退款状态",placeholder:"WPS表中退款状态对应列名"}],ce=[{key:"sync_order_no_col",label:"订单号",placeholder:"WPS表中订单号对应列名"},{key:"sync_production_number_col",label:"生产号码",placeholder:"WPS表中生产号码对应列名"},{key:"sync_iccid_col",label:"ICCID",placeholder:"WPS表中ICCID对应列名"},{key:"sync_puk_col",label:"PUK",placeholder:"WPS表中PUK对应列名"},{key:"sync_express_company_col",label:"快递公司",placeholder:"WPS表中快递公司对应列名"},{key:"sync_tracking_number_col",label:"快递单号",placeholder:"WPS表中快递单号对应列名"},{key:"sync_remark_col",label:"备注",placeholder:"WPS表中备注对应列名"},{key:"sync_fulfillment_status_col",label:"订单状态",placeholder:"WPS表中订单状态对应列名"},{key:"sync_activation_status_col",label:"激活状态",placeholder:"WPS表中激活状态对应列名"},{key:"sync_settlement_status_col",label:"结算状态",placeholder:"WPS表中结算状态对应列名"}],We=[{key:"sync_fulfillment_status_map_0",label:"待支付"},{key:"sync_fulfillment_status_map_1",label:"支付超时"},{key:"sync_fulfillment_status_map_2",label:"已提交"},{key:"sync_fulfillment_status_map_3",label:"初步审核"},{key:"sync_fulfillment_status_map_9",label:"信息待补充"},{key:"sync_fulfillment_status_map_4",label:"待发货"},{key:"sync_fulfillment_status_map_5",label:"已发货"},{key:"sync_fulfillment_status_map_6",label:"待传照片"},{key:"sync_fulfillment_status_map_7",label:"新照待审核"},{key:"sync_fulfillment_status_map_8",label:"审核失败"},{key:"sync_fulfillment_status_map_100",label:"订单已完成"},{key:"sync_fulfillment_status_map_101",label:"订单已取消"}],Be=[{key:"sync_activation_status_map_0",label:"未激活"},{key:"sync_activation_status_map_1",label:"已激活"},{key:"sync_activation_status_map_2",label:"激活且充值"}],$e=[{key:"sync_settlement_status_map_0",label:"未结算"},{key:"sync_settlement_status_map_1",label:"待结算"},{key:"sync_settlement_status_map_2",label:"已结算"},{key:"sync_settlement_status_map_3",label:"拒绝结算"},{key:"sync_settlement_status_map_4",label:"佣金追溯"}],xe=[...We,...Be,...$e],ot=[{key:"fulfillment",title:"订单状态",columnKey:"sync_fulfillment_status_col",fields:We},{key:"activation",title:"激活状态",columnKey:"sync_activation_status_col",fields:Be},{key:"settlement",title:"结算状态",columnKey:"sync_settlement_status_col",fields:$e}],nt=t=>{const e=[];for(let s=0;s<t.length;s+=2)e.push(t.slice(s,s+2));return e},Fe=()=>ce.reduce((t,e)=>(t[e.key]=e.label,t),{}),je=()=>xe.reduce((t,e)=>(t[e.key]=e.label,t),{}),Me=(t=J.map(e=>e.key))=>J.reduce((e,s)=>(t.includes(s.key)&&(e[s.key]=s.label),e),{}),i=Y(X(X({id:0,export_mode:"channel_product",channel_key:"",product_ids:[],table_name:"",sheet_name:"",push_webhook_url:"",callback_trigger_webhook_url:"",remark:"",export_fields:J.map(t=>t.key),export_col_map:Me()},Fe()),je())),st={channel_key:[{validator:(t,e,s)=>{if(i.export_mode!=="all"&&!e){s(new Error("请选择渠道"));return}s()},trigger:"change"}],table_name:[{required:!0,message:"请输入表格名称",trigger:"blur"}],sheet_name:[{required:!0,message:"请输入 Sheet 名称",trigger:"blur"}]},it=Ae(()=>i.export_mode==="all"||!i.channel_key?pe.value:pe.value.filter(t=>t.channel_key===i.channel_key)),ut=t=>{if(t==null||t==="")return"";if(typeof t=="string")try{return JSON.stringify(JSON.parse(t),null,2)}catch(e){return t}try{return JSON.stringify(t,null,2)}catch(e){return String(t)}},dt={id:"ID",config_id:"配置ID",order_id:"订单ID",order_no:"订单号",channel_name:"渠道",event_type:"事件",trigger_source:"来源",webhook_url:"Webhook地址",request_body:"请求体",response_body:"响应体",parsed_row:"解析数据",status:"状态",message:"结果",http_code:"HTTP状态码",retry_count:"重试次数",created_time:"时间"},ze=()=>{const t=Le.value||{},e=["request_body","response_body","parsed_row"],s=["id","config_id","order_id","order_no","channel_name","event_type","trigger_source","webhook_url","request_body","response_body","parsed_row","status","message","http_code","retry_count","created_time"];return[...s,...Object.keys(t).filter(u=>!s.includes(u))].filter(u=>Object.prototype.hasOwnProperty.call(t,u)).map(u=>{const g=t[u];let h=e.includes(u)?ut(g):String(g!=null?g:"");return u==="event_type"&&(h=Ke(h)),u==="trigger_source"&&(h=qe(h)),u==="status"&&(h=Je(h)),{key:u,label:dt[u]||u,value:h,isJson:e.includes(u)}})},pt=Ae(()=>ze().filter(t=>!t.isJson)),ct=Ae(()=>ze().filter(t=>t.isJson)),Ce=t=>{const e=String(t.product_name||"").trim();return e?e.split(/[、,\n\r]+/u).map(s=>s.trim()).filter(Boolean):["全部产品"]},mt=t=>{Oe.value=Ce(t),K.value=!0},gt=()=>{var d;const t="ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";let e="";const s=new Uint8Array(32);(d=window.crypto)==null||d.getRandomValues(s);for(let u=0;u<s.length;u++)e+=t[s[u]%t.length];le.value=e},Ke=t=>({manual_push:"手动推送",manual_config_push:"手动推送",retry_push:"重试推送",photo_reupload:"照片重传",order_created:"订单创建",order_status_changed:"订单状态变更",order_updated:"订单更新"})[String(t||"")]||t||"-",qe=t=>({admin_order_list:"后台订单列表",admin_config_list:"后台配置列表",admin_history_retry:"后台历史重试",history_retry:"后台历史重试",shop_local_order:"店铺下单",admin_reupload:"后台重传照片",open_order_status:"开放接口状态更新",order_callback_service:"订单回调服务",system_timer:"系统定时任务"})[String(t||"")]||t||"-",Je=t=>({success:"成功",failed:"失败",pending:"待处理"})[String(t||"")]||t||"-",He=t=>i.export_fields.includes(t),_t=t=>{const e=i.export_fields.indexOf(t.key);if(e>=0){i.export_fields.splice(e,1);return}if(!String(i.export_col_map[t.key]||"").trim()){S.warning(`请先输入${t.label}列标题`);return}i.export_fields.push(t.key)},ft=()=>({order_no:"订单号",order_create_time:"订单创建时间",product_name:"产品名称",customer_name:"姓名",phone:"电话",idcard:"证件号",address:"地址",id_card_photos:"证件照片",photo_reupload_count:"照片重传次数",custom_order_fields:"自定义字段",production_number:"生产号码",iccid:"ICCID",puk:"PUK",refund_status:"退款状态"}),F=t=>String(t||"").replace(/\\/g,"\\\\").replace(/'/g,"\\'"),Ge=t=>{const e=String(t||"").trim();return e?e.includes("链接")?e.replace(/链接/g,"图片"):e.endsWith("图片")?`${e}附件`:`${e}图片`:"证件照片图片"},yt=t=>{const e=ft(),s=String((t==null?void 0:t.table_name)||"数据表").trim(),d=String((t==null?void 0:t.sheet_name)||"表格视图1").trim();let u=String((t==null?void 0:t.export_fields)||"").split(",").map(_=>String(_||"").trim()).filter(Boolean);u.length||(u=["order_no","product_name","customer_name","phone"]);let g={};try{g=t!=null&&t.export_column_map?JSON.parse(String(t.export_column_map)):{}}catch(_){g={}}const h={order_no:"订单编号",order_create_time:"订单创建时间",product_name:"产品名称",customer_name:"收货人姓名",phone:"收件号码",idcard:"证件号码",address:"收货地址",id_card_photos:"照片链接（3证/4证）",photo_reupload_count:"照片重新上传次数",production_number:"生产号码",iccid:"ICCID",puk:"PUK",refund_status:"已退款"},P={};u.forEach(_=>{const I=String(g[_]||e[_]||_).trim();I&&(P[I]=h[_]||"")});const R={config_id:Number((t==null?void 0:t.id)||0)||0,event_type:"order_created",trigger_source:"system_push",table_name:s,sheet_name:d,target:{table_name:s,sheet_name:d},row:P};return Object.keys(P).forEach(_=>{_ in R||(R[_]=P[_])}),JSON.stringify(R,null,2)},bt=t=>{let e={};try{e=t!=null&&t.export_column_map?JSON.parse(String(t.export_column_map)):{}}catch(R){e={}}const s=String(e.id_card_photos||"证件照片").trim();let d=String((t==null?void 0:t.photo_target_col)||"").trim();d||(d=Ge(s));const u=String(e.order_no||"订单号").trim(),g=String((t==null?void 0:t.table_name)||"数据表").trim(),P=`${String(te.value||window.location.origin||"").replace(/\/api\/cloudexport\.hook\/receive.*$/i,"").replace(/\/$/,"")}/api/image/tobase64`;return String.raw`/*
 * 证件照片自动转图片 AirScript
 * 用法：建议单独创建一条自动化任务执行。
 * 触发器必须只监听源列变更，不能监听整条记录修改；否则写入目标图片列会再次触发自动化。
 *
 * 默认列名：
 * - 数据表：${F(g)}
 * - 源列：${F(s)}
 * - 目标列：${F(d)}
 * - 订单号列：${F(u)}
 * - 转换接口：${F(P)}
 *
 * 可选脚本入参（动态兼容）：
 * - table_name / 数据表
 * - source_col / 源列
 * - target_col / 目标列
 * - order_col / 订单号列
 * - order_no / 订单号
 * - base64_api_url / 转换接口
 */

const DEFAULT_SOURCE_COL = '${F(s)}'
const DEFAULT_TARGET_COL = '${F(d)}'
const DEFAULT_ORDER_COL = '${F(u)}'
const DEFAULT_TABLE_NAME = '${F(g)}'
const DEFAULT_BASE64_API_URL = '${F(P)}'

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

function fileStem(value) {
  var text = normalizeText(value)
  if (!text) return ''
  text = text.split('?')[0].split('#')[0]
  text = text.split('/').pop() || text
  text = text.replace(/\.[^.]+$/, '')
  return text.toLowerCase()
}

function guessMimeType(url, contentType) {
  var ct = normalizeText(contentType).split(';')[0].toLowerCase()
  if (ct.indexOf('image/') === 0) return ct
  var clean = s(url).split('?')[0].toLowerCase()
  if (/\.png$/i.test(clean)) return 'image/png'
  if (/\.webp$/i.test(clean)) return 'image/webp'
  if (/\.gif$/i.test(clean)) return 'image/gif'
  if (/\.bmp$/i.test(clean)) return 'image/bmp'
  return 'image/jpeg'
}

function getSheet(tableName) {
  var sheet = null
  var name = normalizeText(tableName)
  if (name) {
    try {
      if (Application.Sheets && typeof Application.Sheets === 'function') {
        sheet = Application.Sheets(name)
      }
    } catch (e) {}
    try {
      if (!sheet && Application.Sheets && typeof Application.Sheets.Item === 'function') {
        sheet = Application.Sheets.Item(name)
      }
    } catch (e2) {}
  }
  if (!sheet) {
    sheet = Application.ActiveSheet
  }
  if (!sheet) throw new Error('未获取到当前数据表：' + (name || '未指定'))
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

function getRowRange(view, rowIndex) {
  try {
    return view.RecordRange(rowIndex)
  } catch (e) {}
  throw new Error('无法读取记录行范围：row=' + rowIndex)
}

function getRecordCell(view, rowIndex, fieldName) {
  var rowRange = getRowRange(view, rowIndex)
  if (rowRange && typeof rowRange.Item === 'function') {
    return rowRange.Item(1, '@' + fieldName)
  }
  return getCellRange(view, rowIndex, fieldName)
}

function getCellText(view, rowIndex, fieldName) {
  try {
    var rg = getCellRange(view, rowIndex, fieldName)
    if (rg.Text !== undefined && rg.Text !== null) return s(rg.Text)
    if (rg.Value !== undefined && rg.Value !== null) return s(rg.Value)
  } catch (e) {}
  return ''
}

function getAttachmentState(view, rowIndex, fieldName) {
  var state = { count: 0, keys: [] }
  try {
    var rg = getCellRange(view, rowIndex, fieldName)
    var value = rg.Value
    if (value && value.Value !== undefined) value = value.Value
    if (Array.isArray(value)) {
      state.count = value.length
      for (var i = 0; i < value.length; i++) {
        var item = value[i] || {}
        var key = fileStem(item.fileName || item.FileName || item.name || item.Name || item.filename || item.Filename || '')
        if (!key) key = fileStem(item.LinkUrl || item.linkUrl || item.Url || item.url || item.fileData || item.FileData || '')
        if (key) state.keys.push(key)
      }
      return state
    }
    var text = ''
    try {
      text = normalizeText(rg.Text)
    } catch (e1) {}
    if (!text) text = normalizeText(value)
    if (text) {
      var parts = text.split(/\n+|,|，|;|；|\s+/g)
      for (var j = 0; j < parts.length; j++) {
        var partKey = fileStem(parts[j])
        if (partKey) state.keys.push(partKey)
      }
      state.count = state.keys.length || 1
    }
    return state
  } catch (e) {
    return state
  }
}

function getSourceKeys(urls) {
  var out = []
  for (var i = 0; i < urls.length; i++) {
    out.push(fileStem(guessFileName(urls[i], i)))
  }
  return out
}

function shouldSkipByAttachmentState(sourceKeys, targetState) {
  if (!sourceKeys.length) return targetState.count === 0
  if (targetState.keys.length === sourceKeys.length && listEquals(sourceKeys, targetState.keys)) return true
  return false
}

function listEquals(a, b) {
  if (a.length !== b.length) return false
  for (var i = 0; i < a.length; i++) {
    if (a[i] !== b[i]) return false
  }
  return true
}

var IMAGE_CACHE = {}

function objectKeys(obj) {
  try {
    if (!obj || typeof obj !== 'object') return typeof obj
    var keys = []
    for (var k in obj) keys.push(k)
    return keys.join(',')
  } catch (e) {
    return '无法读取'
  }
}

function extractResponseBody(resp) {
  if (resp === null || resp === undefined) return null
  if (typeof resp !== 'object') return resp
  var fields = ['body', 'Body', 'data', 'Data', 'content', 'Content', 'rawBody', 'RawBody', 'result', 'Result', 'response', 'Response']
  for (var i = 0; i < fields.length; i++) {
    var key = fields[i]
    try {
      if (resp[key] !== undefined && resp[key] !== null) return resp[key]
    } catch (e) {}
  }
  var methods = ['arrayBuffer', 'blob', 'text', 'getBody', 'getContent', 'getData']
  for (var j = 0; j < methods.length; j++) {
    var method = methods[j]
    try {
      if (typeof resp[method] === 'function') {
        var val = resp[method]()
        if (val !== undefined && val !== null) return val
      }
    } catch (e2) {}
  }
  return null
}

function parseJson(text) {
  if (typeof text !== 'string') return text
  try {
    return JSON.parse(text)
  } catch (e) {
    throw new Error('转换接口返回非JSON：' + text.slice(0, 200))
  }
}

function requestBase64Batch(apiUrl, imageUrls) {
  var errors = []
  var query = ['max_width=1280', 'quality=75']
  for (var q = 0; q < imageUrls.length; q++) {
    query.push('urls%5B%5D=' + encodeURIComponent(imageUrls[q]))
  }
  var requestUrl = apiUrl + (apiUrl.indexOf('?') >= 0 ? '&' : '?') + query.join('&')
  var candidates = []
  if (typeof HTTP !== 'undefined' && HTTP && typeof HTTP.get === 'function') {
    candidates.push(function() { return HTTP.get(requestUrl) })
  }
  if (typeof HTTP !== 'undefined' && HTTP && typeof HTTP.fetch === 'function') {
    candidates.push(function() { return HTTP.fetch(requestUrl, { method: 'GET', timeout: 20000, headers: { Accept: 'application/json' } }) })
  }
  if (!candidates.length) {
    throw new Error('当前脚本环境缺少 HTTP.get/HTTP.fetch')
  }
  for (var i = 0; i < candidates.length; i++) {
    try {
      var resp = candidates[i]()
      var body = extractResponseBody(resp)
      if (body !== null && body !== undefined) {
        var result = parseJson(body)
        if (Number(result.code || 0) !== 1) {
          throw new Error(result.msg || '转换接口返回失败')
        }
        var rows = result.data || []
        if (!Array.isArray(rows)) rows = [rows]
        if (rows.length !== imageUrls.length) {
          throw new Error('转换接口返回数量不一致：请求' + imageUrls.length + '张，返回' + rows.length + '张')
        }
        var out = []
        for (var r = 0; r < rows.length; r++) {
          var data = rows[r] || {}
          var fileData = data.fileData || data.base64 || ''
          if (!/^data:image\//i.test(fileData)) {
            throw new Error('转换接口第' + (r + 1) + '张未返回data:image格式')
          }
          out.push({
            fileName: data.fileName || guessFileName(imageUrls[r], r),
            fileData: fileData,
            mimeType: data.mimeType || '',
            url: imageUrls[r]
          })
        }
        return out
      }
      errors.push('method#' + (i + 1) + ' 返回体为空，响应字段=' + objectKeys(resp))
    } catch (e) {
      errors.push('method#' + (i + 1) + ' 请求异常=' + s(e && e.message ? e.message : e))
    }
  }
  throw new Error('转换接口请求失败；' + errors.join(' || '))
}

function fetchImages(apiUrl, urls, rowIndex, orderNo) {
  var missing = []
  var missingKeys = []
  for (var i = 0; i < urls.length; i++) {
    var key = s(urls[i])
    if (!IMAGE_CACHE[key]) {
      missing.push(urls[i])
      missingKeys.push(key)
    }
  }
  try {
    if (missing.length) {
      var converted = requestBase64Batch(apiUrl, missing)
      for (var j = 0; j < converted.length; j++) {
        IMAGE_CACHE[missingKeys[j]] = converted[j]
      }
    }
    var out = []
    for (var k = 0; k < urls.length; k++) {
      out.push(IMAGE_CACHE[s(urls[k])])
    }
    return out
  } catch (fetchErr) {
    throw new Error('图片转换失败 | 订单号=' + (s(orderNo) || '未知') + ' | 行号=' + rowIndex + ' | 图片数=' + urls.length + ' | 首个链接=' + (urls[0] || '') + ' | 原始错误=' + s(fetchErr && fetchErr.message ? fetchErr.message : fetchErr))
  }
}

function buildBase64AttachmentPayload(apiUrl, urls, rowIndex, orderNo) {
  var out = []
  var items = fetchImages(apiUrl, urls, rowIndex, orderNo)
  for (var i = 0; i < items.length; i++) {
    var item = items[i]
    out.push({ fileName: item.fileName, fileData: item.fileData })
  }
  return out
}

function makeDBCellValue(payload) {
  if (typeof DBCellValue === 'function') {
    return DBCellValue(payload)
  }
  if (typeof Application !== 'undefined' && Application && typeof Application.DBCellValue === 'function') {
    return Application.DBCellValue(payload)
  }
  throw new Error('当前脚本环境缺少 DBCellValue')
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

function setAttachmentValue(view, rowIndex, fieldName, urls, orderNo, apiUrl) {
  var rg = getRecordCell(view, rowIndex, fieldName)
  if (!urls.length) {
    try { rg.Value = ''; return } catch (e0) {}
    try { rg.Value = makeDBCellValue([]); return } catch (e1) {}
    try { rg.Value = [[makeDBCellValue([])]]; return } catch (e2) {}
    try { rg.SetValues([['']]); return } catch (e3) {}
    throw new Error('清空图片字段失败')
  }
  var payload = buildBase64AttachmentPayload(apiUrl, urls, rowIndex, orderNo)
  var cellValue = null
  try {
    cellValue = makeDBCellValue(payload)
  } catch (dbErr) {
    throw new Error('DBCellValue构造失败：' + s(dbErr && dbErr.message ? dbErr.message : dbErr))
  }
  try {
    rg.Value = cellValue
  } catch (writeErr) {
    throw new Error('图片字段写入失败：' + s(writeErr && writeErr.message ? writeErr.message : writeErr))
  }
}

function processOneRow(view, rowIndex, sourceCol, targetCol, orderCol, apiUrl) {
  var orderNo = getCellText(view, rowIndex, orderCol)
  var sourceText = getCellText(view, rowIndex, sourceCol)
  var urls = splitLinks(sourceText)
  var sourceKeys = getSourceKeys(urls)
  var targetState = getAttachmentState(view, rowIndex, targetCol)
  if (shouldSkipByAttachmentState(sourceKeys, targetState)) {
    console.log('第' + rowIndex + '行已跳过')
    return false
  }
  try {
    setAttachmentValue(view, rowIndex, targetCol, urls, orderNo, apiUrl)
  } catch (err) {
    throw buildAttachmentError(rowIndex, orderNo, targetCol, urls, err)
  }
  if (urls.length) {
    console.log('第' + rowIndex + '行已转图')
  } else {
    console.log('第' + rowIndex + '行已清空图片')
  }
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
  var tableName = normalizeText(getFirstArg(['table_name', '数据表'], DEFAULT_TABLE_NAME)) || DEFAULT_TABLE_NAME
  var sourceCol = normalizeText(getFirstArg(['source_col', '源列'], DEFAULT_SOURCE_COL)) || DEFAULT_SOURCE_COL
  var targetCol = normalizeText(getFirstArg(['target_col', '目标列'], DEFAULT_TARGET_COL)) || DEFAULT_TARGET_COL
  var orderCol = normalizeText(getFirstArg(['order_col', '订单号列'], DEFAULT_ORDER_COL)) || DEFAULT_ORDER_COL
  var base64ApiUrl = normalizeText(getFirstArg(['base64_api_url', '转换接口'], DEFAULT_BASE64_API_URL)) || DEFAULT_BASE64_API_URL

  var sheet = getSheet(tableName)
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
    if (processOneRow(view, targetRow, sourceCol, targetCol, orderCol, base64ApiUrl)) {
      updated = 1
    } else {
      skipped = 1
    }
    return
  }

  for (var rowIndex = 1; rowIndex <= total; rowIndex++) {
    if (processOneRow(view, rowIndex, sourceCol, targetCol, orderCol, base64ApiUrl)) {
      updated++
    } else {
      skipped++
    }
  }
}

main()
`},we=(t,e)=>{ve.value=t,Pe.value=e,de.value=!0},vt=t=>{ke.value=yt(t),we("push","推送示例")},kt=t=>{const e=t,s=Xe(t.export_column_map);W.sourceCol=String(s.id_card_photos||"证件照片").trim(),W.targetCol=String(e.photo_target_col||"")||Ge(W.sourceCol),W.orderCol=String(s.order_no||"订单号").trim(),W.script=bt(t),we("photo","照片转图片示例")},ht=t=>C(null,null,function*(){be.value=!0,we("callback","回调示例");try{const s=(yield Tl(t.id)).data||{},d=s.field_map||{},u=s.status_cols||{},g=(P,R)=>String(d[P]||R).trim(),h=(P,R)=>String(u[P]||R).trim();U.fieldRows=[{label:"订单号",value:g("order_no",t.sync_order_no_col||"订单号")},{label:"生产号码",value:g("production_number",t.sync_production_number_col||"生产号码")},{label:"ICCID",value:g("iccid",t.sync_iccid_col||"ICCID")},{label:"PUK",value:g("puk",t.sync_puk_col||"PUK")},{label:"快递公司",value:g("express_company",t.sync_express_company_col||"快递公司")},{label:"快递单号",value:g("tracking_number",t.sync_tracking_number_col||"快递单号")},{label:"备注",value:g("remark",t.sync_remark_col||"备注")},{label:"订单状态",value:g("fulfillment_status",t.sync_fulfillment_status_col||"订单状态")},{label:"激活状态",value:g("activation_status",t.sync_activation_status_col||"激活状态")},{label:"结算状态",value:g("settlement_status",t.sync_settlement_status_col||"结算状态")}],U.statusRows=[{label:"回传状态",value:h("status","回传状态")},{label:"回传时间",value:h("time","回传时间")},{label:"回传结果",value:h("result","回传结果")},{label:"回传签名",value:h("signature","回传签名")},{label:"已回传签名",value:h("synced_signature","已回传签名")},{label:"失败签名",value:h("failed_signature","失败签名")}],U.triggerBody=String(s.trigger_body||"").trim(),U.pyScript=String(s.script||"").trim(),U.triggerBody||(U.triggerBody=JSON.stringify({action:"callback_sync",config_id:t.id||0,trigger_source:"system_timer",trigger_time:new Date().toISOString().slice(0,19).replace("T"," ")},null,2)),U.pyScript||(U.pyScript="# 回调脚本生成失败，请稍后重试")}finally{be.value=!1}}),Qe=at({props:{loading:Boolean,rows:{type:Array,required:!0},type:{type:String,required:!0}},emits:["detail","retry"],setup(t,{emit:e}){return()=>N(rt,{loading:t.loading,data:t.rows,border:!0,stripe:!0},{default:()=>[N(T,{prop:"order_no",label:"订单号",minWidth:170}),N(T,{prop:"channel_name",label:"渠道",minWidth:130}),t.type==="push"?N(T,{prop:"event_type",label:"事件",width:120},{default:({row:s})=>Ke(String(s.event_type||""))}):null,t.type==="push"?N(T,{prop:"trigger_source",label:"来源",width:130},{default:({row:s})=>qe(String(s.trigger_source||""))}):null,N(T,{prop:"status",label:"状态",width:100,align:"center"},{default:({row:s})=>N(ml,{type:s.status==="success"?"success":s.status==="pending"?"warning":"danger"},()=>Je(String(s.status||"")))}),N(T,{prop:"message",label:"结果",minWidth:240,showOverflowTooltip:!0}),N(T,{prop:"created_time",label:"时间",width:170}),N(T,{label:"操作",width:t.type==="push"?130:86,fixed:"right",align:"center"},{default:({row:s})=>N(ae,null,()=>[t.type==="push"&&s.status==="failed"?N(f,{link:!0,type:"warning",onClick:()=>e("retry",s)},()=>"重试"):null,N(f,{link:!0,type:"primary",onClick:()=>e("detail",s)},()=>"详情")])})]})}}),xt=()=>C(null,null,function*(){var e,s;const t=yield vl();te.value=((e=t.data)==null?void 0:e.webhook_url)||"",le.value=((s=t.data)==null?void 0:s.token)||""}),Ct=()=>C(null,null,function*(){var e,s;const t=yield hl();Re.value=((e=t.data)==null?void 0:e.channels)||[],pe.value=((s=t.data)==null?void 0:s.products)||[]}),M=()=>C(null,null,function*(){G.value=!0;try{const t=yield xl({page:q.page,limit:q.limit});Ie.value=t.data||[],q.total=Number(t.count||0)}finally{G.value=!1}}),wt=()=>C(null,null,function*(){oe.value=!0;try{yield kl(le.value),S.success("保存成功")}finally{oe.value=!1}}),ie=t=>C(null,null,function*(){if(!t){S.warning("内容为空");return}yield navigator.clipboard.writeText(t||""),S.success("已复制")}),St=()=>{Object.assign(i,X(X({id:0,export_mode:"channel_product",channel_key:"",product_ids:[],table_name:"",sheet_name:"",push_webhook_url:"",callback_trigger_webhook_url:"",remark:"",export_fields:J.map(t=>t.key),export_col_map:Me()},Fe()),je()))},Xe=t=>{try{return t?JSON.parse(t):{}}catch(e){return{}}},Et=t=>t.api_name==="自营"?`self:${t.self_channel_id||0}`:t.api_name?`api:${t.api_name}`:"",Ye=t=>C(null,null,function*(){if(St(),t){const e=Xe(t.export_column_map),s=t.export_fields?t.export_fields.split(",").filter(Boolean):J.map(d=>d.key);Object.assign(i,{id:t.id,export_mode:t.export_mode||"channel_product",channel_key:Et(t),product_ids:(t.product_ids||[]).map(d=>Number(d)).filter(Boolean),table_name:t.table_name||"",sheet_name:t.sheet_name||"",push_webhook_url:t.push_webhook_url||"",callback_trigger_webhook_url:t.callback_trigger_webhook_url||"",remark:t.remark||"",export_fields:s,export_col_map:J.reduce((d,u)=>{const g=String(e[u.key]||"");return d[u.key]=g||(s.includes(u.key)?u.label:""),d},{})}),ce.forEach(d=>{i[d.key]=String(t[d.key]||d.label)}),xe.forEach(d=>{i[d.key]=String(t[d.key]||d.label)})}B.value=!0,Ue(()=>{var e;return(e=se.value)==null?void 0:e.clearValidate()})}),Vt=()=>{i.product_ids=[]},Tt=()=>{if(i.export_mode==="all"){i.channel_key="",i.product_ids=[],Ue(()=>{var t;return(t=se.value)==null?void 0:t.clearValidate(["channel_key","product_ids"])});return}i.export_mode==="channel_only"&&(i.product_ids=[],Ue(()=>{var t;return(t=se.value)==null?void 0:t.clearValidate(["product_ids"])}))},At=()=>X(X({id:i.id,export_mode:i.export_mode,channel_key:i.channel_key,product_ids:i.product_ids,product_id:i.product_ids[0]||0,table_name:i.table_name,sheet_name:i.sheet_name,push_webhook_url:i.push_webhook_url,callback_trigger_webhook_url:i.callback_trigger_webhook_url,remark:i.remark,export_fields:i.export_fields,export_col_map:JSON.stringify(i.export_fields.reduce((t,e)=>{const s=String(i.export_col_map[e]||"").trim();return s&&(t[e]=s),t},{}))},ce.reduce((t,e)=>(t[e.key]=i[e.key],t),{})),xe.reduce((t,e)=>(t[e.key]=i[e.key],t),{})),Ut=()=>C(null,null,function*(){var e;if(yield(e=se.value)==null?void 0:e.validate(),i.export_mode!=="all"&&!i.channel_key){S.warning("请选择渠道");return}if(i.export_mode==="channel_product"&&!i.product_ids.length){S.warning("请至少选择一个产品");return}if(!i.export_fields.length){S.warning("请至少选择一个推送字段");return}const t=J.find(s=>i.export_fields.includes(s.key)&&!String(i.export_col_map[s.key]||"").trim());if(t){S.warning(`请先填写${t.label}列标题`);return}ne.value=!0;try{yield Cl(At()),S.success("保存成功"),B.value=!1,yield M()}finally{ne.value=!1}}),Pt=t=>C(null,null,function*(){yield Te.confirm(`确定立即推送配置 #${t.id} 的订单数据吗？`,"确认推送",{type:"warning"});const s=(yield El(t.id)).data||{},d=Number(s.skipped_count||0),u=Number(s.skipped_unpaid_paid_card_count||0),g=d>0?`，跳过 ${d}${u>0?`（付费卡未支付 ${u}）`:""}`:"";S.success(`推送完成：匹配 ${s.matched_total||s.total||0}，推送 ${s.total||0}，成功 ${s.success_count||0}，失败 ${s.failed_count||0}${g}`)}),Rt=t=>C(null,null,function*(){yield Te.confirm(`确定立即执行配置 #${t.id} 的回调同步吗？`,"确认回调",{type:"warning"});const e=yield Vl(t.id);S.success(e.msg||"执行完成")}),It=t=>C(null,null,function*(){he.value=t,ee.value=!0,fe.value=!0,k.enabled=!1,k.interval=5,k.batchSize=50,k.lastTime="",k.lastResult="";try{const s=(yield Al(t.id)).data||{};k.enabled=Number(s.callback_cron_enabled||0)===1,k.interval=Math.max(1,Number(s.callback_cron_interval||5)),k.batchSize=Math.max(1,Number(s.callback_cron_batch_size||50)),k.lastTime=String(s.callback_cron_last_time||""),k.lastResult=String(s.callback_cron_last_result||"")}finally{fe.value=!1}}),Nt=()=>C(null,null,function*(){const t=he.value;if(t){if(k.enabled&&!t.callback_trigger_webhook_url){S.warning("请先在编辑配置里填写回调Webhook");return}ye.value=!0;try{yield Ul({id:t.id,callback_cron_enabled:k.enabled?1:0,callback_cron_interval:k.interval,callback_cron_batch_size:k.batchSize}),S.success("回调设置已保存"),ee.value=!1,yield M()}finally{ye.value=!1}}}),Dt=t=>C(null,null,function*(){yield wl(t.id),S.success("复制成功"),yield M()}),Lt=t=>C(null,null,function*(){yield Te.confirm(`确定删除云导出配置 #${t.id} 吗？`,"确认删除",{type:"warning"}),yield Sl(t.id),S.success("删除成功"),yield M()}),Q=()=>C(null,null,function*(){Z.value=!0;try{const t={page:$.page,limit:$.limit,order_no:j.order_no||"",status:j.status||""};if(x.value==="push"){const e=yield Pl(t);Ne.value=e.data||[],$.total=Number(e.count||0)}else{const e=yield Rl(t);De.value=e.data||[],$.total=Number(e.count||0)}}finally{Z.value=!1}}),Se=()=>{$.page=1,Q()},Ot=()=>{j.order_no="",j.status="",Se()},Ze=t=>{Le.value=t,D.value=!0},Wt=t=>C(null,null,function*(){const e=yield Il(t.id);Number(e.code)===1?S.success(e.msg||"重试成功"):S.error(e.msg||"重试失败"),yield Q()}),Bt=t=>{t==="history"?Q():M()};return _l(()=>C(null,null,function*(){yield Promise.all([xt(),Ct(),M(),Q()])})),(t,e)=>{const s=gl,d=Zt,u=Yt,g=Xt,h=Qt,P=tl,R=Gt,_=rl,I=ol,me=al,Ee=ll,et=nl,$t=sl,Ft=il,jt=ul,ge=dl,Mt=pl,zt=cl,ue=fl("ripple"),Ve=el;return c(),b("div",Nl,[a(et,{modelValue:l(V),"onUpdate:modelValue":e[11]||(e[11]=r=>z(V)?V.value=r:null),onTabChange:Bt},{default:o(()=>[a(R,{label:"导出配置",name:"config"},{default:o(()=>[a(h,{shadow:"never",class:"config-card"},{header:o(()=>[n("div",Dl,[e[35]||(e[35]=n("div",null,[n("span",null,"接收配置"),n("p",null,"表格回调地址和 Token，供云文档脚本回传订单状态。")],-1)),H((c(),L(l(f),{type:"primary",loading:l(oe),onClick:wt},{icon:o(()=>[a(s,{icon:"ri:save-line"})]),default:o(()=>[e[34]||(e[34]=p(" 保存 Token ",-1))]),_:1},8,["loading"])),[[ue]])])]),default:o(()=>[a(g,{gutter:12},{default:o(()=>[a(u,{xs:24,lg:16},{default:o(()=>[a(d,{modelValue:l(te),"onUpdate:modelValue":e[1]||(e[1]=r=>z(te)?te.value=r:null),readonly:""},{prepend:o(()=>[...e[36]||(e[36]=[p("Webhook",-1)])]),append:o(()=>[a(l(f),{onClick:e[0]||(e[0]=r=>ie(l(te)))},{default:o(()=>[...e[37]||(e[37]=[p("复制",-1)])]),_:1})]),_:1},8,["modelValue"])]),_:1}),a(u,{xs:24,lg:8},{default:o(()=>[a(d,{modelValue:l(le),"onUpdate:modelValue":e[2]||(e[2]=r=>z(le)?le.value=r:null),modelModifiers:{trim:!0},"show-password":"",placeholder:"回调 Token"},{prepend:o(()=>[...e[38]||(e[38]=[p("Token",-1)])]),append:o(()=>[a(l(f),{onClick:gt},{default:o(()=>[...e[39]||(e[39]=[p("随机",-1)])]),_:1})]),_:1},8,["modelValue"])]),_:1})]),_:1})]),_:1}),a(h,{shadow:"never"},{header:o(()=>[n("div",Ll,[e[42]||(e[42]=n("div",null,[n("span",null,"任务配置"),n("p",null,"按渠道或渠道+产品建立云文档推送规则。")],-1)),a(l(ae),{wrap:""},{default:o(()=>[H((c(),L(l(f),{type:"primary",onClick:e[3]||(e[3]=r=>Ye())},{icon:o(()=>[a(s,{icon:"ri:add-line"})]),default:o(()=>[e[40]||(e[40]=p(" 新增配置 ",-1))]),_:1})),[[ue]]),H((c(),L(l(f),{loading:l(G),onClick:M},{icon:o(()=>[a(s,{icon:"ri:refresh-line"})]),default:o(()=>[e[41]||(e[41]=p(" 刷新 ",-1))]),_:1},8,["loading"])),[[ue]])]),_:1})])]),default:o(()=>[H((c(),L(l(rt),{data:l(Ie),"row-key":"id",border:"",stripe:""},{default:o(()=>[a(l(T),{prop:"id",label:"ID",width:"78"}),a(l(T),{prop:"export_mode_text",label:"方式",width:"110"}),a(l(T),{prop:"channel_name",label:"产品渠道","min-width":"150"}),a(l(T),{label:"产品","min-width":"240"},{default:o(({row:r})=>[n("div",Ol,[n("div",Wl,v(Ce(r)[0]||"-"),1),Ce(r).length>1?(c(),L(l(f),{key:0,link:"",type:"primary",onClick:w=>mt(r)},{default:o(()=>[...e[43]||(e[43]=[p(" 查看 ",-1)])]),_:1},8,["onClick"])):_e("",!0)])]),_:1}),a(l(T),{prop:"remark",label:"备注","min-width":"160"},{default:o(({row:r})=>[p(v(r.remark||"-"),1)]),_:1}),a(l(T),{label:"推送/回调",width:"210",align:"center"},{default:o(({row:r})=>[a(l(ae),{wrap:""},{default:o(()=>[a(l(f),{link:"",type:"warning",onClick:w=>Pt(r)},{default:o(()=>[...e[44]||(e[44]=[p("推送",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"primary",onClick:w=>Rt(r)},{default:o(()=>[...e[45]||(e[45]=[p("回调",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"primary",onClick:w=>It(r)},{default:o(()=>[...e[46]||(e[46]=[p("回调设置",-1)])]),_:1},8,["onClick"])]),_:2},1024)]),_:1}),a(l(T),{label:"自动化脚本代码",width:"220",align:"center"},{default:o(({row:r})=>[a(l(ae),{wrap:""},{default:o(()=>[a(l(f),{link:"",type:"danger",onClick:w=>vt(r)},{default:o(()=>[...e[47]||(e[47]=[p("推送示例",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"primary",onClick:w=>ht(r)},{default:o(()=>[...e[48]||(e[48]=[p("回调示例",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"primary",onClick:w=>kt(r)},{default:o(()=>[...e[49]||(e[49]=[p("照片转图片示例",-1)])]),_:1},8,["onClick"])]),_:2},1024)]),_:1}),a(l(T),{label:"操作",width:"210",fixed:"right",align:"center"},{default:o(({row:r})=>[a(l(f),{link:"",type:"primary",onClick:w=>Ye(r)},{default:o(()=>[...e[50]||(e[50]=[p("编辑",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"primary",onClick:w=>Dt(r)},{default:o(()=>[...e[51]||(e[51]=[p("复制",-1)])]),_:1},8,["onClick"]),a(l(f),{link:"",type:"danger",onClick:w=>Lt(r)},{default:o(()=>[...e[52]||(e[52]=[p("删除",-1)])]),_:1},8,["onClick"])]),_:1})]),_:1},8,["data"])),[[Ve,l(G)]]),n("div",Bl,[a(P,{"current-page":l(q).page,"onUpdate:currentPage":e[4]||(e[4]=r=>l(q).page=r),"page-size":l(q).limit,"onUpdate:pageSize":e[5]||(e[5]=r=>l(q).limit=r),total:l(q).total,"page-sizes":[20,50,100],layout:"total, sizes, prev, pager, next, jumper",onSizeChange:M,onCurrentChange:M},null,8,["current-page","page-size","total"])])]),_:1})]),_:1}),a(R,{label:"推送历史",name:"history"},{default:o(()=>[a(h,{shadow:"never"},{default:o(()=>[a(Ee,{model:l(j),"label-width":"76px",class:"search-form"},{default:o(()=>[a(g,{gutter:12},{default:o(()=>[a(u,{xs:24,lg:7},{default:o(()=>[a(_,{label:"订单号"},{default:o(()=>[a(d,{modelValue:l(j).order_no,"onUpdate:modelValue":e[6]||(e[6]=r=>l(j).order_no=r),modelModifiers:{trim:!0},clearable:"",placeholder:"订单号",onKeyup:yl(Se,["enter"])},null,8,["modelValue"])]),_:1})]),_:1}),a(u,{xs:24,lg:5},{default:o(()=>[a(_,{label:"状态"},{default:o(()=>[a(me,{modelValue:l(j).status,"onUpdate:modelValue":e[7]||(e[7]=r=>l(j).status=r),clearable:"",placeholder:"全部状态"},{default:o(()=>[a(I,{label:"成功",value:"success"}),a(I,{label:"失败",value:"failed"})]),_:1},8,["modelValue"])]),_:1})]),_:1}),a(u,{xs:24,lg:8},{default:o(()=>[a(_,{class:"search-actions"},{default:o(()=>[a(l(ae),{wrap:""},{default:o(()=>[H((c(),L(l(f),{type:"primary",onClick:Se},{icon:o(()=>[a(s,{icon:"ri:search-line"})]),default:o(()=>[e[53]||(e[53]=p(" 查询 ",-1))]),_:1})),[[ue]]),H((c(),L(l(f),{onClick:Ot},{icon:o(()=>[a(s,{icon:"ri:refresh-line"})]),default:o(()=>[e[54]||(e[54]=p(" 重置 ",-1))]),_:1})),[[ue]])]),_:1})]),_:1})]),_:1})]),_:1})]),_:1},8,["model"]),a(et,{modelValue:l(x),"onUpdate:modelValue":e[8]||(e[8]=r=>z(x)?x.value=r:null),onTabChange:Q},{default:o(()=>[a(R,{label:"推送历史",name:"push"},{default:o(()=>[a(l(Qe),{loading:l(Z),rows:l(Ne),type:"push",onDetail:Ze,onRetry:Wt},null,8,["loading","rows"])]),_:1}),a(R,{label:"表格回调历史",name:"callback"},{default:o(()=>[a(l(Qe),{loading:l(Z),rows:l(De),type:"callback",onDetail:Ze},null,8,["loading","rows"])]),_:1})]),_:1},8,["modelValue"]),n("div",$l,[a(P,{"current-page":l($).page,"onUpdate:currentPage":e[9]||(e[9]=r=>l($).page=r),"page-size":l($).limit,"onUpdate:pageSize":e[10]||(e[10]=r=>l($).limit=r),total:l($).total,"page-sizes":[20,50,100],layout:"total, sizes, prev, pager, next, jumper",onSizeChange:Q,onCurrentChange:Q},null,8,["current-page","page-size","total"])])]),_:1})]),_:1})]),_:1},8,["modelValue"]),a(jt,{modelValue:l(B),"onUpdate:modelValue":e[21]||(e[21]=r=>z(B)?B.value=r:null),title:l(i).id?"编辑配置":"新增配置",size:"860px","destroy-on-close":""},{footer:o(()=>[a(l(f),{onClick:e[20]||(e[20]=r=>B.value=!1)},{default:o(()=>[...e[60]||(e[60]=[p("取消",-1)])]),_:1}),a(l(f),{type:"primary",loading:l(ne),onClick:Ut},{default:o(()=>[...e[61]||(e[61]=[p("保存",-1)])]),_:1},8,["loading"])]),default:o(()=>[a(Ee,{ref_key:"editFormRef",ref:se,model:l(i),rules:st,"label-width":"112px"},{default:o(()=>[a($t,{"content-position":"left"},{default:o(()=>[...e[55]||(e[55]=[p("基础配置",-1)])]),_:1}),a(g,{gutter:12},{default:o(()=>[a(u,{xs:24,lg:12},{default:o(()=>[a(_,{label:"导出方式",prop:"export_mode"},{default:o(()=>[a(Ft,{modelValue:l(i).export_mode,"onUpdate:modelValue":e[12]||(e[12]=r=>l(i).export_mode=r),options:[{label:"全部",value:"all"},{label:"渠道+产品",value:"channel_product"},{label:"渠道",value:"channel_only"}],onChange:Tt},null,8,["modelValue"])]),_:1})]),_:1}),l(i).export_mode!=="all"?(c(),L(u,{key:0,xs:24,lg:12},{default:o(()=>[a(_,{label:"渠道",prop:"channel_key"},{default:o(()=>[a(me,{modelValue:l(i).channel_key,"onUpdate:modelValue":e[13]||(e[13]=r=>l(i).channel_key=r),filterable:"",placeholder:"选择渠道",onChange:Vt},{default:o(()=>[(c(!0),b(E,null,O(l(Re),r=>(c(),L(I,{key:r.key,label:r.name,value:r.key},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})):_e("",!0),l(i).export_mode==="channel_product"?(c(),L(u,{key:1,xs:24},{default:o(()=>[a(_,{label:"产品",prop:"product_ids"},{default:o(()=>[a(me,{modelValue:l(i).product_ids,"onUpdate:modelValue":e[14]||(e[14]=r=>l(i).product_ids=r),multiple:"",filterable:"","collapse-tags":"","collapse-tags-tooltip":"",placeholder:"选择产品"},{default:o(()=>[(c(!0),b(E,null,O(l(it),r=>(c(),L(I,{key:r.id,label:r.name,value:r.id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})):_e("",!0),a(u,{xs:24,lg:12},{default:o(()=>[a(_,{label:"表格名称",prop:"table_name"},{default:o(()=>[a(d,{modelValue:l(i).table_name,"onUpdate:modelValue":e[15]||(e[15]=r=>l(i).table_name=r),modelModifiers:{trim:!0},placeholder:"多维表格名称"},null,8,["modelValue"])]),_:1})]),_:1}),a(u,{xs:24,lg:12},{default:o(()=>[a(_,{label:"Sheet名称",prop:"sheet_name"},{default:o(()=>[a(d,{modelValue:l(i).sheet_name,"onUpdate:modelValue":e[16]||(e[16]=r=>l(i).sheet_name=r),modelModifiers:{trim:!0},placeholder:"Sheet名称"},null,8,["modelValue"])]),_:1})]),_:1}),a(u,{xs:24},{default:o(()=>[a(_,{label:"备注"},{default:o(()=>[a(d,{modelValue:l(i).remark,"onUpdate:modelValue":e[17]||(e[17]=r=>l(i).remark=r),type:"textarea",rows:2,maxlength:"300","show-word-limit":""},null,8,["modelValue"])]),_:1})]),_:1})]),_:1}),n("section",Fl,[e[56]||(e[56]=n("div",{class:"config-section-title"},"推送数据到WPS",-1)),e[57]||(e[57]=n("div",{class:"config-tip danger-tip"},"输入框中的标题必须和 WPS 表格列标题一致；如 WPS 表格标题修改，这里和 WPS 自动化流程需要同步修改。",-1)),a(_,{label:"推送Webhook"},{default:o(()=>[a(d,{modelValue:l(i).push_webhook_url,"onUpdate:modelValue":e[18]||(e[18]=r=>l(i).push_webhook_url=r),modelModifiers:{trim:!0},placeholder:"WPS 自动化 webhook 触发地址"},null,8,["modelValue"])]),_:1}),a(g,{gutter:12,class:"field-grid"},{default:o(()=>[(c(),b(E,null,O(J,r=>a(u,{key:r.key,xs:24,lg:12},{default:o(()=>[a(_,{label:r.label},{default:o(()=>[a(d,{modelValue:l(i).export_col_map[r.key],"onUpdate:modelValue":w=>l(i).export_col_map[r.key]=w,modelModifiers:{trim:!0},placeholder:r.placeholder},{append:o(()=>[a(l(f),{type:He(r.key)?"primary":"",onClick:w=>_t(r)},{default:o(()=>[p(v(He(r.key)?"已启用":"启用"),1)]),_:2},1032,["type","onClick"])]),_:2},1032,["modelValue","onUpdate:modelValue","placeholder"])]),_:2},1032,["label"])]),_:2},1024)),64))]),_:1})]),n("section",jl,[e[58]||(e[58]=n("div",{class:"config-section-title"},"接收WPS回调数据",-1)),e[59]||(e[59]=n("div",{class:"config-tip danger-tip"},"输入框中的标题必须和 WPS 表格列标题一致；如 WPS 表格标题修改，这里和 WPS 自动化流程需要同步修改。",-1)),a(_,{label:"回调Webhook"},{default:o(()=>[a(d,{modelValue:l(i).callback_trigger_webhook_url,"onUpdate:modelValue":e[19]||(e[19]=r=>l(i).callback_trigger_webhook_url=r),modelModifiers:{trim:!0},placeholder:"WPS 回调自动化 webhook 触发地址"},null,8,["modelValue"])]),_:1}),a(g,{gutter:12},{default:o(()=>[(c(),b(E,null,O(ce,r=>a(u,{key:r.key,xs:24,lg:12},{default:o(()=>[a(_,{label:r.label},{default:o(()=>[a(d,{modelValue:l(i)[r.key],"onUpdate:modelValue":w=>l(i)[r.key]=w,modelModifiers:{trim:!0},placeholder:r.placeholder},null,8,["modelValue","onUpdate:modelValue","placeholder"])]),_:2},1032,["label"])]),_:2},1024)),64))]),_:1}),(c(),b(E,null,O(ot,r=>(c(),b(E,{key:r.key},[l(i)[r.columnKey]?(c(),b(E,{key:0},[n("div",Ml,v(r.title)+"映射",1),n("div",zl,v(r.title)+"值如果不是系统默认名称，可在这里填写映射值；多个值用逗号分隔。",1),n("table",Kl,[n("tbody",null,[(c(!0),b(E,null,O(nt(r.fields),w=>(c(),b("tr",{key:w.map(re=>re.key).join("_")},[(c(!0),b(E,null,O(w,re=>(c(),b(E,{key:re.key},[n("th",null,v(re.label),1),n("td",null,[a(d,{modelValue:l(i)[re.key],"onUpdate:modelValue":Kt=>l(i)[re.key]=Kt,modelModifiers:{trim:!0},placeholder:"多个值用逗号分隔"},null,8,["modelValue","onUpdate:modelValue"])])],64))),128))]))),128))])])],64)):_e("",!0)],64))),64))])]),_:1},8,["model"])]),_:1},8,["modelValue","title"]),a(ge,{modelValue:l(D),"onUpdate:modelValue":e[22]||(e[22]=r=>z(D)?D.value=r:null),title:"日志详情",width:"920px"},{default:o(()=>[n("div",ql,[n("table",Jl,[n("tbody",null,[(c(!0),b(E,null,O(l(pt),r=>(c(),b("tr",{key:r.key},[n("th",null,v(r.label),1),n("td",null,v(r.value||"-"),1)]))),128))])]),(c(!0),b(E,null,O(l(ct),r=>(c(),b("div",{key:r.key,class:"log-json-section"},[n("div",Hl,v(r.label),1),n("pre",Gl,v(r.value||"-"),1)]))),128))])]),_:1},8,["modelValue"]),a(ge,{modelValue:l(K),"onUpdate:modelValue":e[23]||(e[23]=r=>z(K)?K.value=r:null),title:"全部产品",width:"560px"},{default:o(()=>[n("div",Ql,[(c(!0),b(E,null,O(l(Oe),r=>(c(),b("div",{key:r,class:"all-product-item"},v(r),1))),128))])]),_:1},8,["modelValue"]),a(ge,{modelValue:l(ee),"onUpdate:modelValue":e[28]||(e[28]=r=>z(ee)?ee.value=r:null),title:"回调设置",width:"640px","destroy-on-close":""},{footer:o(()=>[a(l(f),{onClick:e[27]||(e[27]=r=>ee.value=!1)},{default:o(()=>[...e[64]||(e[64]=[p("取消",-1)])]),_:1}),a(l(f),{type:"primary",loading:l(ye),onClick:Nt},{default:o(()=>[...e[65]||(e[65]=[p("保存",-1)])]),_:1},8,["loading"])]),default:o(()=>[H((c(),L(Ee,{model:l(k),"label-width":"112px",class:"callback-cron-form"},{default:o(()=>[a(_,{label:"启用任务"},{default:o(()=>[a(Mt,{modelValue:l(k).enabled,"onUpdate:modelValue":e[24]||(e[24]=r=>l(k).enabled=r),"active-text":"开启","inactive-text":"关闭"},null,8,["modelValue"]),e[62]||(e[62]=n("div",{class:"form-tip"},"开启后，系统统一定时任务会按频率触发 WPS 的补偿回传 Webhook。",-1))]),_:1}),a(_,{label:"执行频率"},{default:o(()=>[a(me,{modelValue:l(k).interval,"onUpdate:modelValue":e[25]||(e[25]=r=>l(k).interval=r),placeholder:"选择频率"},{default:o(()=>[a(I,{label:"每1分钟",value:1}),a(I,{label:"每5分钟",value:5}),a(I,{label:"每10分钟",value:10}),a(I,{label:"每15分钟",value:15}),a(I,{label:"每30分钟",value:30}),a(I,{label:"每1小时",value:60})]),_:1},8,["modelValue"])]),_:1}),a(_,{label:"每批数量"},{default:o(()=>[a(zt,{modelValue:l(k).batchSize,"onUpdate:modelValue":e[26]||(e[26]=r=>l(k).batchSize=r),min:1,max:500,step:10,"controls-position":"right"},null,8,["modelValue"]),e[63]||(e[63]=n("div",{class:"form-tip"},"单次触发最多处理的记录数，建议 50 左右，数据量大时可适当调高。",-1))]),_:1}),a(_,{label:"回调Webhook"},{default:o(()=>{var r;return[a(d,{"model-value":((r=l(he))==null?void 0:r.callback_trigger_webhook_url)||"",readonly:"",placeholder:"请先在编辑配置里填写回调Webhook"},null,8,["model-value"])]}),_:1}),a(_,{label:"最后执行"},{default:o(()=>[n("div",Xl,v(l(k).lastTime||"从未执行"),1)]),_:1}),a(_,{label:"执行结果"},{default:o(()=>[a(d,{"model-value":l(k).lastResult||"暂无",type:"textarea",rows:3,readonly:""},null,8,["model-value"])]),_:1})]),_:1},8,["model"])),[[Ve,l(fe)]])]),_:1},8,["modelValue"]),a(ge,{modelValue:l(de),"onUpdate:modelValue":e[33]||(e[33]=r=>z(de)?de.value=r:null),title:l(Pe),width:"860px",class:"cloudexport-example-dialog","destroy-on-close":""},{default:o(()=>[H((c(),b("div",Yl,[l(ve)==="push"?(c(),b(E,{key:0},[e[70]||(e[70]=n("div",{class:"example-section"},[n("div",{class:"example-title"},"第1步：在 WPS 新建 webhook 触发"),n("div",{class:"example-desc"},"进入当前多维表自动化，新建一条自动化，触发器选择 webhook 触发。保存后，把 WPS 生成的回调地址填到系统的推送 Webhook 地址里。")],-1)),n("div",Zl,[e[67]||(e[67]=n("div",{class:"example-title"},"第2步：给 webhook 增加请求体，并创建分支",-1)),e[68]||(e[68]=n("div",{class:"example-desc"},[p("下面是系统推送到 WPS 的 JSON 示例。WPS 后续查找/新增记录时要引用 webhook 的请求体字段，例如 "),n("code",null,"系统订单ID"),p("、"),n("code",null,"姓名"),p("，不要引用 webhook 的返回值。")],-1)),e[69]||(e[69]=n("div",{class:"example-copy-label"},"推送请求体示例",-1)),n("pre",er,v(l(ke)),1),a(l(ae),{wrap:""},{default:o(()=>[a(l(f),{type:"primary",onClick:e[29]||(e[29]=r=>ie(l(ke)))},{default:o(()=>[...e[66]||(e[66]=[p("复制请求体示例",-1)])]),_:1})]),_:1})])],64)):l(ve)==="photo"?(c(),b("div",tr,[e[86]||(e[86]=n("div",{class:"example-title"},"照片转图片示例",-1)),e[87]||(e[87]=n("div",{class:"example-desc"},"这段脚本只处理当前多维表。自动化触发器必须只监听证件照片链接列，不能监听整条记录修改。",-1)),n("table",lr,[e[84]||(e[84]=n("tr",null,[n("th",{style:{width:"220px"}},"项目"),n("th",null,"说明")],-1)),n("tr",null,[e[71]||(e[71]=n("td",null,"链接源列",-1)),n("td",null,[n("code",null,v(l(W).sourceCol),1)])]),n("tr",null,[e[72]||(e[72]=n("td",null,"订单号列",-1)),n("td",null,[n("code",null,v(l(W).orderCol),1)])]),n("tr",null,[e[76]||(e[76]=n("td",null,"图片目标列",-1)),n("td",null,[e[73]||(e[73]=p("请先在多维表里新增一个 ",-1)),e[74]||(e[74]=n("code",null,"图片和附件",-1)),e[75]||(e[75]=p(" 字段，字段名填 ",-1)),n("code",null,v(l(W).targetCol),1)])]),n("tr",null,[e[83]||(e[83]=n("td",null,"执行方式",-1)),n("td",null,[e[77]||(e[77]=p("触发器选指定字段变更，只选择 ",-1)),n("code",null,v(l(W).sourceCol),1),e[78]||(e[78]=p("，动作选 ",-1)),e[79]||(e[79]=n("code",null,"执行AirScript脚本",-1)),e[80]||(e[80]=p("。如要只处理当前变动行，请给脚本动作增加参数 ",-1)),e[81]||(e[81]=n("code",null,"order_no",-1)),e[82]||(e[82]=p("，值绑定当前行的订单号变量。",-1))])])]),e[88]||(e[88]=n("div",{class:"example-copy-label"},"AirScript",-1)),n("pre",rr,v(l(W).script),1),a(l(f),{type:"primary",onClick:e[30]||(e[30]=r=>ie(l(W).script))},{default:o(()=>[...e[85]||(e[85]=[p("复制脚本",-1)])]),_:1})])):(c(),b(E,{key:2},[n("div",ar,[e[91]||(e[91]=n("div",{class:"example-title"},"一、回传字段",-1)),e[92]||(e[92]=n("div",{class:"example-desc"},"当前配置会回传给系统的业务字段如下。补偿任务会把这些列逐条 POST 回系统，供后端按 config_id 解析。",-1)),n("table",or,[e[89]||(e[89]=n("tr",null,[n("th",null,"字段"),n("th",null,"列名")],-1)),(c(!0),b(E,null,O(l(U).fieldRows,r=>(c(),b("tr",{key:r.label},[n("td",null,v(r.label),1),n("td",null,[n("code",null,v(r.value),1)])]))),128))]),e[93]||(e[93]=n("div",{class:"example-copy-label"},"回传状态字段",-1)),n("table",nr,[e[90]||(e[90]=n("tr",null,[n("th",null,"字段"),n("th",null,"列名")],-1)),(c(!0),b(E,null,O(l(U).statusRows,r=>(c(),b("tr",{key:r.label},[n("td",null,v(r.label),1),n("td",null,[n("code",null,v(r.value),1)])]))),128))])]),n("div",sr,[e[95]||(e[95]=n("div",{class:"example-title"},"二、系统触发 WPS 的 Webhook 请求体",-1)),e[96]||(e[96]=n("div",{class:"example-copy-label"},"请求体示例",-1)),n("pre",ir,v(l(U).triggerBody),1),a(l(f),{onClick:e[31]||(e[31]=r=>ie(l(U).triggerBody))},{default:o(()=>[...e[94]||(e[94]=[p("复制触发请求体",-1)])]),_:1})]),n("div",ur,[e[98]||(e[98]=n("div",{class:"example-title"},"三、Python 回调脚本",-1)),e[99]||(e[99]=n("div",{class:"example-copy-label"},"脚本",-1)),n("pre",dr,v(l(U).pyScript),1),a(l(f),{type:"primary",onClick:e[32]||(e[32]=r=>ie(l(U).pyScript))},{default:o(()=>[...e[97]||(e[97]=[p("复制 Python 脚本",-1)])]),_:1})])],64))])),[[Ve,l(be)]])]),_:1},8,["modelValue","title"])])}}}),Cr=bl(pr,[["__scopeId","data-v-cee1a7f5"]]);export{Cr as default};
