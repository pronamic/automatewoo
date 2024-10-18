!function(){var e={694:function(e,t,r){"use strict";var n=r(925);function a(){}function o(){}o.resetWarningCache=a,e.exports=function(){function e(e,t,r,a,o,i){if(i!==n){var s=new Error("Calling PropTypes validators directly is not supported by the `prop-types` package. Use PropTypes.checkPropTypes() to call them. Read more at http://fb.me/use-check-prop-types");throw s.name="Invariant Violation",s}}function t(){return e}e.isRequired=e;var r={array:e,bigint:e,bool:e,func:e,number:e,object:e,string:e,symbol:e,any:e,arrayOf:t,element:e,elementType:e,instanceOf:t,node:e,objectOf:t,oneOf:t,oneOfType:t,shape:t,exact:t,checkPropTypes:o,resetWarningCache:a};return r.PropTypes=r,r}},556:function(e,t,r){e.exports=r(694)()},925:function(e){"use strict";e.exports="SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED"},942:function(e,t){var r;!function(){"use strict";var n={}.hasOwnProperty;function a(){for(var e="",t=0;t<arguments.length;t++){var r=arguments[t];r&&(e=i(e,o(r)))}return e}function o(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return a.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var r in e)n.call(e,r)&&e[r]&&(t=i(t,r));return t}function i(e,t){return t?e?e+" "+t:e+t:e}e.exports?(a.default=a,e.exports=a):void 0===(r=function(){return a}.apply(t,[]))||(e.exports=r)}()}},t={};function r(n){var a=t[n];if(void 0!==a)return a.exports;var o=t[n]={exports:{}};return e[n](o,o.exports,r),o.exports}r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,{a:t}),t},r.d=function(e,t){for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){"use strict";function e(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=Array(t);r<t;r++)n[r]=e[r];return n}function t(t,r){if(t){if("string"==typeof t)return e(t,r);var n={}.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?e(t,r):void 0}}function n(r){return function(t){if(Array.isArray(t))return e(t)}(r)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(r)||t(r)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}var a=window.wp.hooks,o=window.wp.i18n,i=window.React,s=window.wc.components,l=window.wp.apiFetch,c=r.n(l),u=window.wp.url,m=window.wc.navigation,p=function(e){return e},d=function(e){return{key:e.id,label:e.title}},f={name:"workflows",options:function(e){return c()({path:"/automatewoo/workflows?"+new URLSearchParams({search:e,per_page:10,orderby:"popularity",_fields:"id,title"}).toString()})},getOptionIdentifier:function(e){return e.id},getOptionLabel:function(e){return e.title},getOptionKeywords:function(e){return[e.title]},getOptionCompletion:d},y=[{label:(0,o.__)("Show","automatewoo"),staticParams:["section","paged","per_page"],param:"filter",showFilters:function(){return!0},filters:[{label:(0,o.__)("All Workflows","automatewoo"),value:"all"},{label:(0,o.__)("Single Workflow","automatewoo"),value:"select_workflow",subFilters:[{component:"Search",value:"single_workflow",path:["select_workflow"],settings:{type:"custom",param:"workflows",getLabels:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:p;return function(){var r=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"",n="function"==typeof e?e(arguments.length>1?arguments[1]:void 0):e,a=(0,m.getIdsFromQuery)(r);if(a.length<1)return Promise.resolve([]);var o={include:a.join(","),per_page:a.length};return c()({path:(0,u.addQueryArgs)(n,o)}).then((function(e){return e.map(t)}))}}("/automatewoo/workflows",d),labels:{placeholder:(0,o.__)("Type to search for a workflow","automatewoo"),button:(0,o.__)("Single Workflow","automatewoo")},autocompleter:f}}]}]}];function g(e){return g="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},g(e)}function b(e){var t=function(e){if("object"!=g(e)||!e)return e;var t=e[Symbol.toPrimitive];if(void 0!==t){var r=t.call(e,"string");if("object"!=g(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e);return"symbol"==g(t)?t:t+""}function h(e,t,r){return(t=b(t))in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function v(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function w(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,b(n.key),n)}}function _(e,t,r){return t&&w(e.prototype,t),r&&w(e,r),Object.defineProperty(e,"prototype",{writable:!1}),e}function O(e,t){if(t&&("object"==g(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}function k(e){return k=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(e){return e.__proto__||Object.getPrototypeOf(e)},k(e)}function E(e,t){return E=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},E(e,t)}function S(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&E(e,t)}var C=window.wp.element,R=window.wp.compose,T=window.wp.date,q=window.wp.data,P=window.lodash,D=r(556),j=r.n(D),x=window.wc.data,F=window.wc.date,N=window.wc.currency,A=r.n(N),I=window.wc.wcSettings,L=(0,I.getSetting)("currency"),B=A()(L),Q=function(e){var t=B.getCurrencyConfig(),r=(0,a.applyFilters)("woocommerce_admin_report_currency",t,e);return A()(r)},M=(0,C.createContext)(B);function V(e){var t=e.className,r=(0,o.__)("There was an error getting your stats. Please try again.","woocommerce"),n=(0,o.__)("Reload","woocommerce");return(0,i.createElement)(s.EmptyContent,{className:t,title:r,actionLabel:n,actionCallback:function(){window.location.reload()}})}V.propTypes={className:j().string};var U=V;function H(e,t){var r=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{};if(!e||0===e.length)return null;var n=e.slice(0),a=n.pop();if(a.showFilters(t,r)){var o=(0,m.flattenFilters)(a.filters),i=t[a.param]||a.defaultValue||"all";return(0,P.find)(o,{value:i})}return H(n,t,r)}function W(e){return function(t){return(0,T.format)(e,t)}}function Y(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function K(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?Y(Object(r),!0).forEach((function(t){h(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):Y(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function z(e,t,r){return t=k(t),O(e,G()?Reflect.construct(t,r||[],k(e).constructor):t.apply(e,r))}function G(){try{var e=!Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){})))}catch(e){}return(G=function(){return!!e})()}var J=function(e){function t(){return v(this,t),z(this,t,arguments)}return S(t,e),_(t,[{key:"shouldComponentUpdate",value:function(e){return e.isRequesting!==this.props.isRequesting||e.primaryData.isRequesting!==this.props.primaryData.isRequesting||e.secondaryData.isRequesting!==this.props.secondaryData.isRequesting||!(0,P.isEqual)(e.query,this.props.query)}},{key:"getItemChartData",value:function(){var e=this.props,t=e.primaryData,r=e.selectedChart;return t.data.intervals.map((function(e){var t={};return e.subtotals.segments.forEach((function(e){if(e.segment_label){var n=t[e.segment_label]?e.segment_label+" (#"+e.segment_id+")":e.segment_label;t[e.segment_id]={label:n,value:e.subtotals[r.key]||0}}})),K({date:(0,T.format)("Y-m-d\\TH:i:s",e.date_start)},t)}))}},{key:"getTimeChartData",value:function(){var e=this.props,t=e.query,r=e.primaryData,n=e.secondaryData,a=e.selectedChart,o=e.defaultDateRange,i=(0,F.getIntervalForQuery)(t,o),s=(0,F.getCurrentDates)(t,o),l=s.primary,c=s.secondary;return r.data.intervals.map((function(e,r){var o=(0,F.getPreviousDate)(e.date_start,l.after,c.after,t.compare,i),s=n.data.intervals[r];return{date:(0,T.format)("Y-m-d\\TH:i:s",e.date_start),primary:{label:"".concat(l.label," (").concat(l.range,")"),labelDate:e.date_start,value:e.subtotals[a.key]||0},secondary:{label:"".concat(c.label," (").concat(c.range,")"),labelDate:o.format("YYYY-MM-DD HH:mm:ss"),value:s&&s.subtotals[a.key]||0}}}))}},{key:"getTimeChartTotals",value:function(){var e=this.props,t=e.primaryData,r=e.secondaryData,n=e.selectedChart;return{primary:(0,P.get)(t,["data","totals",n.key],null),secondary:(0,P.get)(r,["data","totals",n.key],null)}}},{key:"renderChart",value:function(e,t,r,n){var a=this.props,l=a.emptySearchResults,c=a.filterParam,u=a.interactiveLegend,m=a.itemsLabel,p=a.legendPosition,d=a.path,f=a.query,y=a.selectedChart,g=a.showHeaderControls,b=a.primaryData,h=a.defaultDateRange,v=(0,F.getIntervalForQuery)(f,h),w=(0,F.getAllowedIntervalsForQuery)(f,h),_=(0,F.getDateFormatsForInterval)(v,b.data.intervals.length,{type:"php"}),O=l?(0,o.__)("No data for the current search","woocommerce"):(0,o.__)("No data for the selected date range","woocommerce"),k=this.context,E=k.formatAmount,S=k.getCurrencyConfig;return(0,i.createElement)(s.Chart,{allowedIntervals:w,data:r,dateParser:"%Y-%m-%dT%H:%M:%S",emptyMessage:O,filterParam:c,interactiveLegend:u,interval:v,isRequesting:t,itemsLabel:m,legendPosition:p,legendTotals:n,mode:e,path:d,query:f,screenReaderFormat:W(_.screenReaderFormat),showHeaderControls:g,title:y.label,tooltipLabelFormat:W(_.tooltipLabelFormat),tooltipTitle:"time-comparison"===e&&y.label||null,tooltipValueFormat:(0,x.getTooltipValueFormat)(y.type,E),chartType:(0,F.getChartTypeForQuery)(f),valueType:y.type,xFormat:W(_.xFormat),x2Format:W(_.x2Format),currency:S()})}},{key:"renderItemComparison",value:function(){var e=this.props,t=e.isRequesting,r=e.primaryData;if(r.isError)return(0,i.createElement)(U,null);var n=t||r.isRequesting,a=this.getItemChartData();return this.renderChart("item-comparison",n,a)}},{key:"renderTimeComparison",value:function(){var e=this.props,t=e.isRequesting,r=e.primaryData,n=e.secondaryData;if(!r||r.isError||n.isError)return(0,i.createElement)(U,null);var a=t||r.isRequesting||n.isRequesting,o=this.getTimeChartData(),s=this.getTimeChartTotals();return this.renderChart("time-comparison",a,o,s)}},{key:"render",value:function(){return"item-comparison"===this.props.mode?this.renderItemComparison():this.renderTimeComparison()}}])}(C.Component);J.contextType=M,J.propTypes={filters:j().array,isRequesting:j().bool,itemsLabel:j().string,limitProperties:j().array,mode:j().string,path:j().string.isRequired,primaryData:j().object,query:j().object.isRequired,secondaryData:j().object,selectedChart:j().shape({key:j().string.isRequired,label:j().string.isRequired,order:j().oneOf(["asc","desc"]),orderby:j().string,type:j().oneOf(["average","number","currency"]).isRequired}).isRequired},J.defaultProps={isRequesting:!1,primaryData:{data:{intervals:[]},isError:!1,isRequesting:!1},secondaryData:{data:{intervals:[]},isError:!1,isRequesting:!1}};var X=(0,R.compose)((0,q.withSelect)((function(e,t){var r=t.charts,n=t.endpoint,a=t.filters,o=t.isRequesting,i=t.limitProperties,s=t.query,l=t.advancedFilters,c=i||[n],u=H(a,s),m=(0,P.get)(u,["settings","param"]),p=t.mode||function(e,t){if(e&&t){var r=(0,P.get)(e,["settings","param"]);if(!r||Object.keys(t).includes(r))return(0,P.get)(e,["chartMode"])}return null}(u,s)||"time-comparison",d=e(x.SETTINGS_STORE_NAME).getSetting("wc_admin","wcAdminSettings").woocommerce_default_date_range,f=e(x.REPORTS_STORE_NAME),y={mode:p,filterParam:m,defaultDateRange:d};if(o)return y;var g=c.some((function(e){return s[e]&&s[e].length}));if(s.search&&!g)return K(K({},y),{},{emptySearchResults:!0});var b=r&&r.map((function(e){return e.key})),h=(0,x.getReportChartData)({endpoint:n,dataType:"primary",query:s,selector:f,limitBy:c,filters:a,advancedFilters:l,defaultDateRange:d,fields:b});if("item-comparison"===p)return K(K({},y),{},{primaryData:h});var v=(0,x.getReportChartData)({endpoint:n,dataType:"secondary",query:s,selector:f,limitBy:c,filters:a,advancedFilters:l,defaultDateRange:d,fields:b});return K(K({},y),{},{primaryData:h,secondaryData:v})})))(J),$=window.wc.number,Z=window.wc.tracks;function ee(e,t,r){return t=k(t),O(e,te()?Reflect.construct(t,r||[],k(e).constructor):t.apply(e,r))}function te(){try{var e=!Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){})))}catch(e){}return(te=function(){return!!e})()}var re=function(e){function t(){return v(this,t),ee(this,t,arguments)}return S(t,e),_(t,[{key:"formatVal",value:function(e,t){var r=this.context,n=r.formatAmount,a=r.getCurrencyConfig;return"currency"===t?n(e):(0,$.formatValue)(a(),t,e)}},{key:"getValues",value:function(e,t){var r=this.props,n=r.emptySearchResults,a=r.summaryData.totals,o=a.primary?a.primary[e]:0,i=a.secondary?a.secondary[e]:0,s=n?0:o,l=n?0:i;return{delta:(0,$.calculateDelta)(s,l),prevValue:this.formatVal(l,t),value:this.formatVal(s,t)}}},{key:"render",value:function(){var e=this,t=this.props,r=t.charts,n=t.query,a=t.selectedChart,l=t.summaryData,c=t.endpoint,u=t.report,p=t.defaultDateRange,d=l.isError,f=l.isRequesting;if(d)return(0,i.createElement)(U,null);if(f)return(0,i.createElement)(s.SummaryListPlaceholder,{numberOfItems:r.length});var y=(0,F.getDateParamsFromQuery)(n,p).compare;return(0,i.createElement)(s.SummaryList,null,(function(t){var n=t.onToggle;return r.map((function(t){var r=t.key,l=t.order,p=t.orderby,d=t.label,f=t.type,g=t.isReverseTrend,b=t.labelTooltipText,h={chart:r};p&&(h.orderby=p),l&&(h.order=l);var v=(0,m.getNewPath)(h),w=a.key===r,_=e.getValues(r,f),O=_.delta,k=_.prevValue,E=_.value;return(0,i.createElement)(s.SummaryNumber,{key:r,delta:O,href:v,label:d,reverseTrend:g,prevLabel:"previous_period"===y?(0,o.__)("Previous period:","woocommerce"):(0,o.__)("Previous year:","woocommerce"),prevValue:k,selected:w,value:E,labelTooltipText:b,onLinkClickCallback:function(){n&&n(),(0,Z.recordEvent)("analytics_chart_tab_click",{report:u||c,key:r})}})}))}))}}])}(C.Component);re.propTypes={charts:j().array.isRequired,endpoint:j().string.isRequired,limitProperties:j().array,query:j().object.isRequired,selectedChart:j().shape({key:j().string.isRequired,label:j().string.isRequired,order:j().oneOf(["asc","desc"]),orderby:j().string,type:j().oneOf(["average","number","currency"]).isRequired}).isRequired,summaryData:j().object,report:j().string},re.defaultProps={summaryData:{totals:{primary:{},secondary:{}},isError:!1}},re.contextType=M;var ne=(0,R.compose)((0,q.withSelect)((function(e,t){var r=t.charts,n=t.endpoint,a=t.limitProperties,o=t.query,i=t.filters,s=t.advancedFilters,l=a||[n],c=l.some((function(e){return o[e]&&o[e].length}));if(o.search&&!c)return{emptySearchResults:!0};var u=r&&r.map((function(e){return e.key})),m=e(x.SETTINGS_STORE_NAME).getSetting("wc_admin","wcAdminSettings").woocommerce_default_date_range;return{summaryData:(0,x.getSummaryNumbers)({endpoint:n,query:o,select:e,limitBy:l,filters:i,advancedFilters:s,defaultDateRange:m,fields:u}),defaultDateRange:m}})))(re);function ae(e,r){return function(e){if(Array.isArray(e))return e}(e)||function(e,t){var r=null==e?null:"undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(null!=r){var n,a,o,i,s=[],l=!0,c=!1;try{if(o=(r=r.call(e)).next,0===t){if(Object(r)!==r)return;l=!1}else for(;!(l=(n=o.call(r)).done)&&(s.push(n.value),s.length!==t);l=!0);}catch(e){c=!0,a=e}finally{try{if(!l&&null!=r.return&&(i=r.return(),Object(i)!==i))return}finally{if(c)throw a}}return s}}(e,r)||t(e,r)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function oe(e,t,r,n,a,o,i){try{var s=e[o](i),l=s.value}catch(e){return void r(e)}s.done?t(l):Promise.resolve(l).then(n,a)}function ie(e){return function(){var t=this,r=arguments;return new Promise((function(n,a){var o=e.apply(t,r);function i(e){oe(o,n,a,i,s,"next",e)}function s(e){oe(o,n,a,i,s,"throw",e)}i(void 0)}))}}var se=window.regeneratorRuntime,le=r.n(se),ce=window.wp.components,ue=window.AutomateWoo.Modal,me=r.n(ue);function pe(e,t){if(null==e)return{};var r,n,a=function(e,t){if(null==e)return{};var r={};for(var n in e)if({}.hasOwnProperty.call(e,n)){if(t.includes(n))continue;r[n]=e[n]}return r}(e,t);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);for(n=0;n<o.length;n++)r=o[n],t.includes(r)||{}.propertyIsEnumerable.call(e,r)&&(a[r]=e[r])}return a}var de=window.wp.dom,fe=window.wc.csvExport,ye=r(942),ge=r.n(ye),be=function(){return(0,i.createElement)("svg",{role:"img","aria-hidden":"true",focusable:"false",version:"1.1",xmlns:"http://www.w3.org/2000/svg",x:"0px",y:"0px",viewBox:"0 0 24 24"},(0,i.createElement)("path",{d:"M18,9c-0.009,0-0.017,0.002-0.025,0.003C17.72,5.646,14.922,3,11.5,3C7.91,3,5,5.91,5,9.5c0,0.524,0.069,1.031,0.186,1.519 C5.123,11.016,5.064,11,5,11c-2.209,0-4,1.791-4,4c0,1.202,0.541,2.267,1.38,3h18.593C22.196,17.089,23,15.643,23,14 C23,11.239,20.761,9,18,9z M12,16l-4-5h3V8h2v3h3L12,16z"}))};function he(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function ve(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?he(Object(r),!0).forEach((function(t){h(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):he(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}var we=["className","getHeadersContent","getRowsContent","getSummary","isRequesting","primaryData","tableData","endpoint","itemIdField","tableQuery","compareBy","compareParam","searchBy","labels","checkboxes","renderActionButton"],_e=["updateUserPreferences"];function Oe(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function ke(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?Oe(Object(r),!0).forEach((function(t){h(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):Oe(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}var Ee=function(e){var t=e.className,r=e.getHeadersContent,l=e.getRowsContent,c=e.getSummary,u=e.isRequesting,p=e.primaryData,d=e.tableData,f=e.endpoint,y=(e.itemIdField,e.tableQuery,e.compareBy),g=e.compareParam,b=e.searchBy,v=e.labels,w=void 0===v?{}:v,_=e.checkboxes,O=e.renderActionButton,k=pe(e,we),E=e.query,S=e.columnPrefsKey,R=d.items,T=d.query,q=E[g]?(0,m.getIdsFromQuery)(E[y]):[],D=ae((0,C.useState)(q),2),j=D[0],F=D[1],N=(0,C.useRef)(null),A=(0,x.useUserPreferences)(),I=A.updateUserPreferences,L=pe(A,_e);if(d.isError||p.isError)return(0,i.createElement)(U,null);var B=[];S&&(B=L&&L[S]?L[S]:B);var Q,M,V,H,W=function(e,t,n){var o=c?c(t,n):null;return(0,a.applyFilters)("woocommerce_admin_report_table",{endpoint:f,headers:r(),rows:l(e),totals:t,summary:o,items:R})},Y=function(t,r){var a=e.ids;if(r)F((0,P.uniq)([a[t]].concat(n(j))));else{var o=j.indexOf(a[t]);F([].concat(n(j.slice(0,o)),n(j.slice(o+1))))}},K=function(t){var r=e.ids,n=void 0===r?[]:r,a=-1!==j.indexOf(n[t]);return{display:(0,i.createElement)(ce.CheckboxControl,{onChange:(0,P.partial)(Y,t),checked:a}),value:!1}},z=u||d.isRequesting||p.isRequesting,G=(0,P.get)(p,["data","totals"],{}),J=R.totalResults||0,X=J>0,$=(0,m.getSearchWords)(E).map((function(e){return{key:e,label:e}})),ee=R.data,te=W(ee,G,J),re=te.headers,ne=te.rows,oe=te.summary;(_||y)&&(ne=ne.map((function(e,t){return[K(t)].concat(n(e))})),re=[(Q=e.ids,M=void 0===Q?[]:Q,V=M.length>0,H=V&&M.length===j.length,{cellClassName:"is-checkbox-column",key:"compare",label:(0,i.createElement)(ce.CheckboxControl,{onChange:function(t){var r=e.ids;F(t?r:[])},"aria-label":(0,o.__)("Select All","woocommerce"),checked:H,disabled:!V}),required:!0})].concat(n(re)));var ie=function(e,t){return t?e.map((function(e){return ke(ke({},e),{},{visible:e.required||!t.includes(e.key)})})):e.map((function(e){return ke(ke({},e),{},{visible:e.required||!e.hiddenByDefault})}))}(re,B);return(0,i.createElement)(C.Fragment,null,(0,i.createElement)("div",{className:"woocommerce-report-table__scroll-point automatewoo-clone",ref:N,"aria-hidden":!0}),(0,i.createElement)(s.TableCard,ke({className:ge()("woocommerce-report-table","automatewoo-clone",t),hasSearch:!!b,actions:[O&&O({selectedRows:j}),y&&(0,i.createElement)(s.CompareButton,{key:"compare",className:"woocommerce-table__compare",count:j.length,helpText:w.helpText||(0,o.__)("Check at least two items below to compare","woocommerce"),onClick:function(){y&&(0,m.onQueryChange)("compare")(y,g,j.join(","))},disabled:!X},w.compareButton||(0,o.__)("Compare","woocommerce")),b&&(0,i.createElement)(s.Search,{allowFreeTextSearch:!0,inlineTags:!0,key:"search",onChange:function(t){var r=e.baseSearchQuery,n=e.addCesSurveyForCustomerSearch,a=t.map((function(e){return e.label.replace(",","%2C")}));a.length?((0,m.updateQueryString)(ke(ke(h(h({filter:void 0},g,void 0),b,void 0),r),{},{search:(0,P.uniq)(a).join(",")})),n()):(0,m.updateQueryString)({search:void 0}),(0,Z.recordEvent)("analytics_table_filter",{report:f})},placeholder:w.placeholder||(0,o.__)("Search by item name","woocommerce"),selected:$,showClearButton:!0,type:b,disabled:!X}),X&&(0,i.createElement)(ce.Button,{key:"download",className:"woocommerce-table__download-button",disabled:z,onClick:function(){var t=e.createNotice,r=e.startExport,n=e.title,a=Object.assign({},E),i=R.data,s=R.totalResults,l="browser";if(delete a.extended_info,a.search&&delete a[b],i&&i.length===s){var c=W(i,s),u=c.headers,m=c.rows;(0,fe.downloadCSVFile)((0,fe.generateCSVFileName)(n,a),(0,fe.generateCSVDataFromTable)(u,m))}else l="email",r(f,T).then((function(){return t("success",(0,o.sprintf)(/* translators: %s = type of report */ /* translators: %s = type of report */
(0,o.__)("Your %s Report will be emailed to you.","woocommerce"),n))})).catch((function(e){return t("error",e.message||(0,o.sprintf)(/* translators: %s = type of report */ /* translators: %s = type of report */
(0,o.__)("There was a problem exporting your %s Report. Please try again.","woocommerce"),n))}));(0,Z.recordEvent)("analytics_table_download",{report:f,rows:s,download_type:l})}},(0,i.createElement)(be,null),(0,i.createElement)("span",{className:"woocommerce-table__download-button__label"},w.downloadButton||(0,o.__)("Download","woocommerce")))],headers:ie,isLoading:z,onQueryChange:m.onQueryChange,onColumnsChange:function(e,t){var r=re.map((function(e){return e.key})).filter((function(t){return!e.includes(t)}));if(S){var n=h({},S,r);I(n)}if(t){var a={report:f,column:t,status:e.includes(t)?"on":"off"};(0,Z.recordEvent)("analytics_table_header_toggle",a)}},onSort:function(e,t){(0,m.onQueryChange)("sort")(e,t);var r={report:f,column:e,direction:t};(0,Z.recordEvent)("analytics_table_sort",r)},onPageChange:function(e,t){N.current.scrollIntoView();var r=N.current.nextSibling.querySelector(".woocommerce-table__table"),n=de.focus.focusable.find(r);n.length&&n[0].focus(),t&&("goto"===t?(0,Z.recordEvent)("analytics_table_go_to_page",{report:f,page:e}):(0,Z.recordEvent)("analytics_table_page_click",{report:f,direction:t}))},rows:ne,rowsPerPage:parseInt(T.per_page,10)||x.QUERY_DEFAULTS.pageSize,summary:oe,totalRows:J},k)))};Ee.propTypes={className:j().string,baseSearchQuery:j().object,compareBy:j().string,compareParam:j().string,columnPrefsKey:j().string,endpoint:j().string,extendItemsMethodNames:j().shape({getError:j().string,isRequesting:j().string,load:j().string}),extendedItemsStoreName:j().string,getHeadersContent:j().func.isRequired,getRowsContent:j().func.isRequired,getSummary:j().func,itemIdField:j().string,labels:j().shape({compareButton:j().string,downloadButton:j().string,helpText:j().string,placeholder:j().string}),primaryData:j().object,searchBy:j().string,summaryFields:j().arrayOf(j().string),tableData:j().object.isRequired,tableQuery:j().object,title:j().string.isRequired,checkboxes:j().bool,renderActionButton:j().func},Ee.defaultProps={primaryData:{},tableData:{items:{data:[],totalResults:0},query:{}},tableQuery:{},compareParam:"filter",downloadable:!1,onSearch:P.noop,baseSearchQuery:{}};var Se=[],Ce={},Re=(0,R.compose)((0,q.withSelect)((function(e,t){var r=t.endpoint,n=t.getSummary,a=t.isRequesting,o=t.itemIdField,i=t.query,s=t.tableData,l=t.tableQuery,c=t.filters,u=t.advancedFilters,m=t.summaryFields,p=t.extendedItemsStoreName,d=e(x.REPORTS_STORE_NAME),f=p?e(p):null,y=e(x.SETTINGS_STORE_NAME).getSetting("wc_admin","wcAdminSettings").woocommerce_default_date_range,g=i.search&&!(i[r]&&i[r].length);if(a||g)return Ce;var b="categories"===r?"products":r,h=n?(0,x.getReportChartData)({endpoint:b,selector:d,dataType:"primary",query:i,filters:c,advancedFilters:u,defaultDateRange:y,fields:m}):Ce,v=s||(0,x.getReportTableData)({endpoint:r,query:i,selector:d,tableQuery:l,filters:c,advancedFilters:u,defaultDateRange:y}),w=f?function(e,t,r){var n=t.extendItemsMethodNames,a=t.itemIdField,o=r.items.data;if(!(Array.isArray(o)&&o.length&&n&&a))return r;var i=e[n.getError],s=e[n.isRequesting],l=e[n.load],c={include:o.map((function(e){return e[a]})).join(","),per_page:o.length},u=l(c),m=!!s&&s(c),p=!!i&&i(c),d=o.map((function(e){var t=(0,P.first)(u.filter((function(t){return e.id===t.id})));return ve(ve({},e),t)})),f=r.isRequesting||m,y=r.isError||p;return ve(ve({},r),{},{isRequesting:f,isError:y,items:ve(ve({},r.items),{},{data:d})})}(f,t,v):v;return{primaryData:h,ids:o&&w.items.data?w.items.data.map((function(e){return e[o]})):Se,tableData:w,query:i}})),(0,q.withDispatch)((function(e){var t=e(x.EXPORT_STORE_NAME).startExport;return{createNotice:e("core/notices").createNotice,startExport:t,addCesSurveyForCustomerSearch:e("wc/customer-effort-score").addCesSurveyForCustomerSearch}})))(Ee),Te=function(){var e=ie(le().mark((function e(t){var r,n;return le().wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=(0,q.dispatch)("core/notices"),n=r.createNotice,e.prev=1,e.next=4,c()({path:"/automatewoo/conversions/batch/",method:"DELETE",body:JSON.stringify({ids:t})});case 4:e.next=10;break;case 6:return e.prev=6,e.t0=e.catch(1),n("error",(0,o.__)("There was an error unmarking conversions.","automatewoo")),e.abrupt("return");case 10:n("success",(0,o.__)("Orders successfully unmarked as conversions.","automatewoo"));case 11:case"end":return e.stop()}}),e,null,[[1,6]])})));return function(_x){return e.apply(this,arguments)}}();function qe(e){var t=e.query,r=e.filters,n=(0,q.useDispatch)(x.REPORTS_STORE_NAME).invalidateResolutionForStoreSelector,a=(0,I.getSetting)("admin",{}).dateFormat||F.defaultTableDateFormat,l=Q(t),c=l.formatAmount,u=l.getCurrencyConfig,m=ae((0,C.useState)(!1),2),p=m[0],d=m[1],f=(0,C.useCallback)(function(){var e=ie(le().mark((function e(t){return le().wrap((function(e){for(;;)switch(e.prev=e.next){case 0:if(p){e.next=7;break}return d(!0),e.next=4,Te(t);case 4:d(!1),n("getReportStats"),n("getReportItems");case 7:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),[p,n]);return(0,i.createElement)(Re,{className:"aw-conversions-table",endpoint:"conversions",getHeadersContent:function(){return[{key:"order",label:(0,o.__)("Order #","automatewoo"),screenReaderLabel:(0,o.__)("Order Number","automatewoo"),required:!0},{key:"customer",label:(0,o.__)("Customer","automatewoo"),isLeftAligned:!0},{key:"Workflow",label:(0,o.__)("Workflow","automatewoo"),isLeftAligned:!0},{key:"log",label:(0,o.__)("Log","automatewoo"),isLeftAligned:!0},{key:"interacted",label:(0,o.__)("First Interacted","automatewoo")},{key:"placed",label:(0,o.__)("Order Placed","automatewoo")},{key:"total",label:(0,o.__)("Order Total","automatewoo"),isCurrency:!0,isNumeric:!0}]},getRowsContent:function(e){return e.map((function(e){var t=e.order_id,r=e.order_number,n=e.workflow_id,o=e.conversion_id,l=e.date_created,u=e.total_sales,m=e.extended_info,p=m.conversion.date_opened,d=m.customer,f=d.user_id,y=d.first_name,g=d.last_name,b=m.workflow.name,h="".concat(y," ").concat(g);return[{display:(0,i.createElement)(s.Link,{href:"post.php?post=".concat(t,"&action=edit"),type:"wp-admin"},(0,i.createElement)("strong",null,r)),value:t},{display:null===f?h:(0,i.createElement)(s.Link,{href:"user-edit.php?user_id=".concat(f,"&action=edit"),type:"wp-admin"},h),value:"".concat(h," (").concat(null===f?"guest":f,")")},{display:(0,i.createElement)(s.Link,{href:"post.php?post=".concat(n,"&action=edit"),type:"wp-admin"},(0,i.createElement)("strong",null,b)),value:n},{display:(0,i.createElement)("a",{className:me().triggerClasses.openLink,href:"admin-ajax.php?action=aw_modal_log_info&log_id=".concat(o)},o),value:o},{display:(0,i.createElement)(s.Date,{date:p,visibleFormat:a}),value:p},{display:(0,i.createElement)(s.Date,{date:l,visibleFormat:a}),value:l},{display:c(u),value:u}]}))},getSummary:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0,r=e.total_sales,n=void 0===r?0:r,a=e.net_revenue,i=void 0===a?0:a,s=u();return[{label:(0,o._n)("Conversion","Conversions",t,"automatewoo"),value:(0,$.formatValue)(s,"number",t)},{label:(0,o.__)("Total sales","automatewoo"),value:c(n)},{label:(0,o.__)("Net sales","automatewoo"),value:c(i)}]},summaryFields:["total_sales","net_revenue","orders_count"],itemIdField:"order_id",query:t,tableQuery:{orderby:t.orderby||"date",order:t.order||"desc",extended_info:!0},title:(0,o.__)("Conversions list","automatewoo"),columnPrefsKey:"conversions_report_columns",filters:r,checkboxes:!0,renderActionButton:function(e){var t=e.selectedRows;return(0,i.createElement)(ce.Tooltip,{text:(0,o.__)("Unmark selected orders as conversions","automatewoo")},(0,i.createElement)(ce.Button,{disabled:p||0===t.length,variant:"secondary",onClick:function(){return f(t)}},(0,o.__)("Unmark","automatewoo")))}})}var Pe=[{key:"total_sales",href:"",label:(0,o.__)("Total value","automatewoo"),labelTooltipText:(0,o.__)("Converted order value","automatewoo"),type:"currency"},{key:"net_revenue",href:"",label:(0,o.__)("Net revenue","automatewoo"),type:"currency"},{key:"orders_count",href:"",label:(0,o.__)("Orders","automatewoo"),labelTooltipText:(0,o.__)("Converted orders","automatewoo"),type:"number"}];function De(e){var t=e.query,r=e.path,n=Pe.find((function(e){return e.key===t.chart}))||Pe[0];return(0,i.createElement)(i.Fragment,null,(0,i.createElement)(s.ReportFilters,{query:t,path:r,filters:y}),(0,i.createElement)(ne,{charts:Pe,endpoint:"conversions",query:t,selectedChart:n,filters:y}),(0,i.createElement)(X,{endpoint:"conversions",path:r,query:t,filters:y,selectedChart:n,charts:Pe}),(0,i.createElement)(qe,{query:t,filters:y}))}var je=[{key:"sent",href:"",label:(0,o.__)("Sent","automatewoo"),labelTooltipText:(0,o.__)("Trackable messages sent","automatewoo"),type:"number"},{key:"opens",href:"",label:(0,o.__)("Opens","automatewoo"),labelTooltipText:(0,o.__)("Unique opens","automatewoo"),type:"number"},{key:"unique-clicks",href:"",label:(0,o.__)("Unique clicks","automatewoo"),type:"number"},{key:"clicks",href:"",label:(0,o.__)("Clicks","automatewoo"),type:"number"}],xe=[{key:"unsubscribers",href:"",label:(0,o.__)("Unsubscribers","automatewoo"),type:"number"}];function Fe(e){var t=e.query,r=e.path,n=je.find((function(e){return e.key===t.chart}))||je[0];return(0,i.createElement)(i.Fragment,null,(0,i.createElement)(s.ReportFilters,{query:t,path:r,filters:y}),(0,i.createElement)(ne,{charts:je,endpoint:"email-tracking",query:t,selectedChart:n,filters:y}),(0,i.createElement)(X,{endpoint:"email-tracking",path:r,query:t,filters:y,selectedChart:n,charts:je}),(0,i.createElement)(ne,{charts:xe,endpoint:"unsubscribers",query:t,selectedChart:xe[0],filters:y}),(0,i.createElement)(X,{endpoint:"unsubscribers",path:r,query:t,filters:y,selectedChart:xe[0],charts:xe}))}var Ne=[{key:"runs",label:(0,o.__)("Runs","automatewoo"),labelTooltipText:(0,o.__)("Workflows have run for the selected period","automatewoo"),type:"number"}];function Ae(e){var t=e.query,r=e.path;return(0,i.createElement)(i.Fragment,null,(0,i.createElement)(s.ReportFilters,{query:t,path:r,filters:y}),(0,i.createElement)(ne,{charts:Ne,endpoint:"workflow-runs",query:t,selectedChart:Ne[0],filters:y}),(0,i.createElement)(X,{endpoint:"workflow-runs",path:r,query:t,filters:y,selectedChart:Ne[0],charts:Ne}))}(0,a.addFilter)("woocommerce_admin_reports_list","automatewoo",(function(e){return[].concat(n(e),[{report:"automatewoo-runs-by-date",title:(0,o._x)("Workflows","analytics report title","automatewoo"),component:Ae,navArgs:{id:"automatewoo-analytics-runs-by-date"}},{report:"automatewoo-email-tracking",title:(0,o._x)("Email & SMS Tracking","analytics report title","automatewoo"),component:Fe,navArgs:{id:"automatewoo-analytics-email-tracking"}},{report:"automatewoo-conversions",title:(0,o._x)("Conversions","analytics report title","automatewoo"),component:De,navArgs:{id:"automatewoo-analytics-conversions"}}])}))}()}();