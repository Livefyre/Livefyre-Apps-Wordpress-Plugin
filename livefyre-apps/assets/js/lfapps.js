var LFAPPS = {};

var lf_extend = function(a, b) {
    for(var key in b)
        if(b.hasOwnProperty(key))
            a[key] = b[key];
    return a;
};

/*
 * Javascript Event Listeners
 */

//init new jsevents array
LFAPPS.jsevent_listeners = [];

//add new listener for a specific app, event_name and callback
LFAPPS.add_jsevent_listener = function(app, event_name, callback) {
    LFAPPS.jsevent_listeners.push({app:app, event_name:event_name, callback:callback});
};

//get jsevent listeners for a specific app
LFAPPS.get_app_jsevent_listeners = function(app) {
    var events = [];
    if(LFAPPS.jsevent_listeners.length > 0) {
        for(var i in LFAPPS.jsevent_listeners) {
            var event_obj = LFAPPS.jsevent_listeners[i];
            if(event_obj.app === app) {
                events.push(event_obj);
            }
        }
    }
    return events;
};