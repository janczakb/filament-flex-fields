"use strict";var FffFlexFieldAssetInspector=(()=>{var i=Object.defineProperty;var c=Object.getOwnPropertyDescriptor;var u=Object.getOwnPropertyNames;var f=Object.prototype.hasOwnProperty;var p=(t,e)=>{for(var s in e)i(t,s,{get:e[s],enumerable:!0})},l=(t,e,s,r)=>{if(e&&typeof e=="object"||typeof e=="function")for(let n of u(e))!f.call(t,n)&&n!==s&&i(t,n,{get:()=>e[n],enumerable:!(r=c(e,n))||r.enumerable});return t};var a=t=>l(i({},"__esModule",{value:!0}),t);var d={};p(d,{createAssetInspector:()=>o});function o({getLoadedUrls:t=()=>[]}={}){if(typeof t!="function")throw new TypeError("createAssetInspector requires getLoadedUrls to be a function.");return{listUrls(){return t().filter(e=>typeof e=="string"&&e!=="")},duplicateHrefs(){let e=new Map,s=[];for(let r of this.listUrls()){if(e.has(r)){s.includes(r)||s.push(r);continue}e.set(r,!0)}return s},inspect(){return{urls:this.listUrls(),duplicates:this.duplicateHrefs()}}}}typeof window<"u"&&(window.createAssetInspector=o,window.FffAssetInspector={create:o});return a(d);})();
/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */
window.createAssetInspector = FffFlexFieldAssetInspector.createAssetInspector;window.FffAssetInspector = { create: FffFlexFieldAssetInspector.createAssetInspector };
