'use strict';

var ReverseStreamComponent = React.createClass({
    displayName: 'ReverseStreamComponent',

    getInitialState: function getInitialState() {
        var cursor = this.props.client.openCursor(this.props.query);
        var pager = new LivefyreTimeline.models.simple.SimplePager(cursor, { autoLoad: true, size: 50 });
        pager.on('readable', this.onReadable.bind(this));
        pager.on('error', function (event) {
            // log('error', event);
        });
        pager.on('initialized', function (event) {
            // log("initialized");
        });

        pager.on('end', (function () {
            // log("pager issued end");
            this.setState({ done: true, estimated: false });
        }).bind(this));

        return {
            cursor: cursor,
            pager: pager,
            items: [],
            count: undefined,
            estimated: true,
            done: false,
            seen: {}
        };
    },

    onReadable: function onReadable(event) {
        // console.log("readable:", event);
        var pager = this.state.pager;
        var data = pager.read({ loadOnFault: false });
        var new_items = this.state.items;
        var seen = this.state.seen;
        if (Array.isArray(data)) {
            // log("read:", data.length);
            data.map(function (item) {
                // console.log("" + item.tuuid + ": " + item.verb + ", " + item.published);
                if (seen[item.tuuid]) {
                    // console.log("duplicate!");
                    return;
                }
                seen[item.tuuid] = true;
                new_items.push(item);
            });
        } else {
            // log("unexpected; data is:", data);
        }
        if (pager.done()) {
            // log("we're at the end of the stream", pager.done(), pager.cursor.hasNext());
        }

        this.setState({
            items: new_items,
            estimated: !pager.done(),
            count: new_items.length,
            done: pager.done()
        });
    },

    loadMore: function loadMore() {
        this.state.pager.loadNextPage();
    },

    renderItem: function renderItem(item) {
        return React.createElement(this.props.itemComponent, { item: item, key: item.tuuid });
    },

    bunchLikes: function bunchLikes(items) {
        //Bunch likes using JS :)
        return items;
    },

    render: function render() {
        var ReactCSSTransitionGroup = React.addons.CSSTransitionGroup;
        var more = this.state.estimated ? React.createElement(
            'button',
            { onClick: this.loadMore },
            'Load more'
        ) : "";
        var plus = this.state.estimated ? '+' : '';
        var bunchedItems = this.bunchLikes(this.state.items);
        return React.createElement(
            'div',
            null,
            React.createElement(
                'h2',
                null,
                'Item Count: ',
                this.state.count,
                plus
            ),
            React.createElement(
                ReactCSSTransitionGroup,
                { transitionName: 'newActivity' },
                bunchedItems.map((function (item) {
                    return this.renderItem(item);
                }).bind(this))
            ),
            more,
            React.createElement('br', null),
            close
        );
    }
});

var AlertActivityItem = React.createClass({
    displayName: 'AlertActivityItem',

    render: function render() {
        var i = this.props.item;
        var who = i.actor.handle || i.actor.displayName;
        var verb = i.verb == 'post' ? 'replied' : i.verb;
        var where = i.target;
        var what = i.object.content;
        console.log(where);
        var original = i.verb == 'like' ? i.object.content : i.object.inReplyTo.content;
        var when = new Date(i.published);

        console.log(when);
        return React.createElement(
            'div',
            null,
            React.createElement('hr', null),
            React.createElement(
                'span',
                null,
                'Your post in ',
                React.createElement(
                    'a',
                    { href: where.url },
                    where.title
                )
            ),
            ':',
            React.createElement('div', { dangerouslySetInnerHTML: { __html: original } }),
            React.createElement('div', { dangerouslySetInnerHTML: { __html: what } }),
            React.createElement(
                'span',
                null,
                who,
                ' ',
                verb,
                ' @ ',
                React.createElement(
                    'i',
                    null,
                    when.toString()
                )
            )
        );
    }
});

var UserStream = React.createClass({
    displayName: 'UserStream',

    getInitialState: function getInitialState() {
        var user = this.props.user;
        var Precondition = LivefyreTimeline.Precondition;
        var RecentQuery = LivefyreTimeline.backends.chronos.cursors.RecentQuery;
        var ConnectionFactory = LivefyreTimeline.backends.factory('production', {
            token: user.token
        });
        var realChronos = ConnectionFactory.chronos();
        var realQuery = RecentQuery("urn:livefyre:" + user.id.split("@")[1] + ":user=" + user.id.split("@")[0] + ":alertStream", 50);

        return {
            user: user,
            precondition: Precondition,
            chronos: realChronos,
            query: realQuery
        };
    },

    closeModal: function closeModal() {
        jQuery('#activityStreamWrapper').hide();
    },

    render: function render() {
        var streamName = this.state.user.displayName + "'s Steam";
        var close = React.createElement(
            'button',
            { onClick: this.closeModal },
            'Close'
        );
        jQuery('#activityStreamWrapper').hide();
        return React.createElement(
            'div',
            { id: 'activityStream' },
            React.createElement(ReverseStreamComponent, { itemComponent: AlertActivityItem, client: this.state.chronos, query: this.state.query }),
            close
        );
    }
});

function buildRealStream(user) {
    React.render(React.createElement(UserStream, { user: user }), document.getElementById('activityStreamWrapper'));
}