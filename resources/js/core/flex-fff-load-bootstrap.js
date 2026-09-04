/**
 * Early boot for x-fff-load — must load in SCRIPTS_BEFORE @filamentScripts
 * so the directive registers on alpine:init before Alpine.start().
 */
import './flex-fff-load-directive.js'
