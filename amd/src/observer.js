// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * observer.js
 *
 * @package   mod_videoobserve
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/notification'
], function(Ajax, Notification) {

    /**
     * Video Observation player, tracking and observation controls.
     *
     * Supports Moodle-hosted/direct HTML5 video, YouTube and Vimeo.
     */
    const loadScript = (src, key) => {
        if (window[key]) {
            return Promise.resolve(window[key]);
        }

        const promiseKey = '__videoobserve_' + key;

        if (window[promiseKey]) {
            return window[promiseKey];
        }

        window[promiseKey] = new Promise((resolve, reject) => {
            const script = document.createElement('script');

            script.src = src;
            script.async = true;
            script.onload = () => resolve(window[key]);
            script.onerror = reject;

            document.head.appendChild(script);
        });

        return window[promiseKey];
    };

    const html5Adapter = (region, source) => new Promise((resolve, reject) => {
        if (!source.url) {
            reject(new Error('Missing video URL'));
            return;
        }

        const video = document.createElement('video');

        video.controls = true;
        video.preload = 'metadata';
        video.className = 'videoobserve-video';

        if (source.poster) {
            video.poster = source.poster;
        }

        video.src = source.url;

        region.replaceChildren(video);

        const adapter = {
            current: 0,
            duration: 0,
            playing: false,

            getCurrentTime: () => video.currentTime || 0,

            getDuration: () => video.duration || 0,

            isPlaying: () => !video.paused && !video.ended,

            seek: t => {
                video.currentTime = Math.max(0, t);
            },

            play: () => video.play(),

            pause: () => video.pause()
        };

        video.addEventListener(
            'loadedmetadata',
            () => resolve(adapter),
            {once: true}
        );

        video.addEventListener(
            'error',
            () => reject(new Error('Unable to load video')),
            {once: true}
        );
    });

    const youtubeAdapter = (region, source) => loadScript(
        'https://www.youtube.com/iframe_api',
        'YT'
    ).catch(() => null).then(() => new Promise((resolve, reject) => {

        const create = () => {
            if (!window.YT || !window.YT.Player) {
                setTimeout(create, 100);
                return;
            }

            if (!source.videoid) {
                reject(new Error('Invalid YouTube id'));
                return;
            }

            const holder = document.createElement('div');

            region.replaceChildren(holder);

            let state = -1;

            const player = new window.YT.Player(holder, {
                videoId: source.videoid,

                playerVars: {
                    rel: 0
                },

                events: {
                    onReady: () => resolve({
                        getCurrentTime: () => player.getCurrentTime() || 0,

                        getDuration: () => player.getDuration() || 0,

                        isPlaying: () =>
                            state === window.YT.PlayerState.PLAYING,

                        seek: t =>
                            player.seekTo(Math.max(0, t), true),

                        play: () =>
                            player.playVideo(),

                        pause: () =>
                            player.pauseVideo()
                    }),

                    onStateChange: e => {
                        state = e.data;
                    },

                    onError: () => reject(
                        new Error('Unable to load YouTube video')
                    )
                }
            });
        };

        create();
    }));

    const vimeoAdapter = (region, source) => loadScript(
        'https://player.vimeo.com/api/player.js',
        'Vimeo'
    ).then(() => new Promise((resolve, reject) => {

        if (!source.videoid) {
            reject(new Error('Invalid Vimeo id'));
            return;
        }

        const iframe = document.createElement('iframe');

        iframe.src =
            'https://player.vimeo.com/video/' +
            encodeURIComponent(source.videoid);

        iframe.allow =
            'autoplay; fullscreen; picture-in-picture';

        iframe.allowFullscreen = true;
        iframe.className = 'videoobserve-vimeo';

        region.replaceChildren(iframe);

        const player = new window.Vimeo.Player(iframe);

        let current = 0;
        let duration = 0;
        let playing = false;

        player.on('timeupdate', d => {
            current = d.seconds || 0;
            duration = d.duration || duration;
        });

        player.on('play', () => {
            playing = true;
        });

        player.on('pause', () => {
            playing = false;
        });

        player.on('ended', () => {
            playing = false;
        });

        player.ready()
            .then(() => player.getDuration())
            .then(d => {
                duration = d || 0;

                resolve({
                    getCurrentTime: () => current,

                    getDuration: () => duration,

                    isPlaying: () => playing,

                    seek: t =>
                        player.setCurrentTime(Math.max(0, t)),

                    play: () =>
                        player.play(),

                    pause: () =>
                        player.pause()
                });
            })
            .catch(reject);
    }));

    const makeAdapter = (region, source) => {
        if (source.source === 'youtube') {
            return youtubeAdapter(region, source);
        }

        if (source.source === 'vimeo') {
            return vimeoAdapter(region, source);
        }

        return html5Adapter(region, source);
    };

    const call = (method, args) => Ajax.call([{
        methodname: method,
        args: args
    }])[0];

    const init = config => {
        const root = document.getElementById(
            'videoobserve-app'
        );

        if (!root) {
            return;
        }

        const region = root.querySelector(
            '[data-region="player"]'
        );

        let adapter = null;
        let duration = 0;
        let pendingStart = null;
        let pendingEnd = null;
        let previousTime = null;
        let lastFlush = Date.now();

        const intervals = new Map();

        const showError = err => {
            Notification.exception(err);
        };

        const progressUpdate = result => {
            const label = root.querySelector(
                '[data-region="percentlabel"]'
            );

            const bar = root.querySelector(
                '[data-region="progressbar"]'
            );

            if (label) {
                label.textContent =
                    Number(result.percent).toFixed(1) + '%';
            }

            if (bar) {
                bar.style.width =
                    Math.max(
                        0,
                        Math.min(
                            100,
                            Number(result.percent)
                        )
                    ) + '%';

                const progress = bar.closest('.progress');

                if (progress) {
                    progress.setAttribute(
                        'aria-valuenow',
                        String(result.percent)
                    );
                }
            }
        };

        const flushProgress = () => {
            if (!config.tracking || !adapter) {
                return;
            }

            const position = adapter.getCurrentTime();

            const start = pendingStart === null
                ? position
                : pendingStart;

            const end = pendingEnd === null
                ? position
                : pendingEnd;

            pendingStart = null;
            pendingEnd = null;
            lastFlush = Date.now();

            call(
                'mod_videoobserve_update_progress',
                {
                    cmid: config.cmid,
                    segmentstart: start,
                    segmentend: end,
                    position: position,

                    duration:
                        duration ||
                        adapter.getDuration() ||
                        0
                }
            ).then(progressUpdate).catch(() => {
            });
        };

        const timeline = () => {
            const line = root.querySelector(
                '[data-region="timeline"]'
            );

            if (!line || !duration) {
                return;
            }

            line.replaceChildren();

            root.querySelectorAll(
                '[data-region="occurrences"] [data-action="seek"]'
            ).forEach(button => {

                const time = Number(
                    button.dataset.time || 0
                );

                const marker =
                    document.createElement('button');

                marker.type = 'button';
                marker.className = 'videoobserve-marker';

                marker.style.left =
                    Math.max(
                        0,
                        Math.min(
                            100,
                            time / duration * 100
                        )
                    ) + '%';

                marker.title =
                    button.textContent.trim();

                marker.setAttribute(
                    'aria-label',
                    button.textContent.trim()
                );

                marker.addEventListener(
                    'click',
                    () => adapter.seek(time)
                );

                line.appendChild(marker);
            });
        };

        const syncSummary = eventid => {
            const card = root.querySelector(
                '[data-eventid="' + eventid + '"]'
            );

            const row = root.querySelector(
                '[data-summary-eventid="' +
                eventid +
                '"]'
            );

            if (!card || !row) {
                return;
            }

            const list = card.querySelector(
                '[data-region="occurrences"]'
            );

            const items = [
                ...list.querySelectorAll(
                    '[data-occurrence-id]'
                )
            ];

            const count = row.querySelector(
                '[data-summary="count"]'
            );

            if (count) {
                count.textContent =
                    String(items.length);
            }

            const moments = row.querySelector(
                '[data-summary="moments"]'
            );

            const notes = row.querySelector(
                '[data-summary="notes"]'
            );

            if (moments) {
                moments.replaceChildren();

                items.forEach(item => {
                    const source = item.querySelector(
                        '[data-action="seek"]'
                    );

                    if (source) {
                        const b =
                            document.createElement('button');

                        b.type = 'button';

                        b.className =
                            'btn btn-link btn-sm p-0 me-2';

                        b.dataset.action = 'seek';
                        b.dataset.time = source.dataset.time;
                        b.textContent = source.textContent;

                        b.addEventListener(
                            'click',
                            () => adapter.seek(
                                Number(
                                    b.dataset.time || 0
                                )
                            )
                        );

                        moments.appendChild(b);
                    }
                });
            }

            if (notes) {
                notes.replaceChildren();

                items.forEach(item => {
                    const note = item.querySelector(
                        '[data-region="savednote"]'
                    );

                    if (
                        note &&
                        note.textContent.trim()
                    ) {
                        const d =
                            document.createElement('div');

                        d.textContent =
                            note.textContent;

                        notes.appendChild(d);
                    }
                });
            }
        };

        const addItem = result => {
            const list = root.querySelector(
                '[data-region="occurrences"]' +
                '[data-eventid="' +
                result.eventtypeid +
                '"]'
            );

            if (!list) {
                return;
            }

            const li =
                document.createElement('li');

            li.className =
                'list-group-item px-0 py-2';

            li.dataset.occurrenceId = result.id;
            li.dataset.eventid = result.eventtypeid;
            li.dataset.start = result.starttime;
            li.dataset.end = result.endtime;

            const wrap =
                document.createElement('div');

            wrap.className =
                'd-flex justify-content-between ' +
                'align-items-start gap-2';

            const left =
                document.createElement('div');

            const seek =
                document.createElement('button');

            seek.type = 'button';

            seek.className =
                'btn btn-link btn-sm p-0 fw-bold';

            seek.dataset.action = 'seek';
            seek.dataset.time = result.starttime;
            seek.textContent = result.timecode;

            seek.addEventListener(
                'click',
                () => adapter.seek(
                    Number(result.starttime)
                )
            );

            left.appendChild(seek);

            if (result.note) {
                const note =
                    document.createElement('div');

                note.className = 'small mt-1';
                note.dataset.region = 'savednote';
                note.textContent = result.note;

                left.appendChild(note);
            }

            const del =
                document.createElement('button');

            del.type = 'button';

            del.className =
                'btn btn-link btn-sm text-danger';

            del.dataset.action = 'delete';
            del.dataset.id = result.id;
            del.textContent = config.strings.delete;

            wrap.append(left, del);
            li.appendChild(wrap);
            list.appendChild(li);

            const card = list.closest(
                '[data-eventid]'
            );

            const count = card
                ? card.querySelector(
                    '[data-region="eventcount"]'
                )
                : null;

            if (count) {
                count.textContent =
                    String(result.eventcount);
            }

            const total = root.querySelector(
                '[data-region="totalcount"]'
            );

            if (
                total &&
                result.totalcount !== undefined
            ) {
                total.textContent =
                    String(result.totalcount);
            }

            syncSummary(result.eventtypeid);
            timeline();
        };

        const save = (eventid, start, end) => {
            const card = root.querySelector(
                '[data-eventid="' + eventid + '"]'
            );

            const noteElement = card
                ? card.querySelector(
                    '[data-region="note"]'
                )
                : null;

            const note = noteElement
                ? noteElement.value
                : '';

            const method =
                config.mode === 'reference'
                    ? 'mod_videoobserve_save_reference'
                    : 'mod_videoobserve_save_occurrence';

            call(method, {
                cmid: config.cmid,
                eventtypeid: Number(eventid),
                starttime: start,
                endtime: end || 0,
                note: note
            }).then(result => {

                if (card) {
                    const input = card.querySelector(
                        '[data-region="note"]'
                    );

                    if (input) {
                        input.value = '';
                    }
                }

                addItem(result);

            }).catch(showError);
        };

        root.addEventListener('click', event => {
            const button = event.target.closest(
                'button[data-action]'
            );

            if (
                !button ||
                !root.contains(button) ||
                !adapter
            ) {
                return;
            }

            const action = button.dataset.action;

            if (action === 'seek') {
                adapter.seek(
                    Number(button.dataset.time || 0)
                );

                return;
            }

            if (action === 'mark') {
                save(
                    button.dataset.eventid,
                    adapter.getCurrentTime(),
                    0
                );

                return;
            }

            if (action === 'interval') {
                const eventid =
                    button.dataset.eventid;

                if (!intervals.has(eventid)) {
                    intervals.set(
                        eventid,
                        adapter.getCurrentTime()
                    );

                    button.textContent =
                        config.strings.finishinterval;

                    button.classList.add('active');

                } else {
                    const start =
                        intervals.get(eventid);

                    intervals.delete(eventid);

                    button.textContent =
                        config.strings.startinterval;

                    button.classList.remove('active');

                    save(
                        eventid,
                        start,
                        adapter.getCurrentTime()
                    );
                }

                return;
            }

            if (action === 'delete') {
                if (
                    !window.confirm(
                        config.strings.deleteconfirm
                    )
                ) {
                    return;
                }

                const item = button.closest(
                    '[data-occurrence-id]'
                );

                const eventid = item
                    ? item.dataset.eventid
                    : null;

                const args = {
                    cmid: config.cmid
                };

                const method =
                    config.mode === 'reference'
                        ? 'mod_videoobserve_delete_reference'
                        : 'mod_videoobserve_delete_occurrence';

                if (config.mode === 'reference') {
                    args.referenceid =
                        Number(button.dataset.id);

                } else {
                    args.occurrenceid =
                        Number(button.dataset.id);
                }

                call(method, args)
                    .then(result => {

                        if (item) {
                            item.remove();
                        }

                        const card =
                            root.querySelector(
                                '[data-eventid="' +
                                eventid +
                                '"]'
                            );

                        const count = card
                            ? card.querySelector(
                                '[data-region="eventcount"]'
                            )
                            : null;

                        if (count) {
                            count.textContent =
                                String(
                                    result.eventcount
                                );
                        }

                        const total =
                            root.querySelector(
                                '[data-region="totalcount"]'
                            );

                        if (
                            total &&
                            result.totalcount !== undefined
                        ) {
                            total.textContent =
                                String(
                                    result.totalcount
                                );
                        }

                        syncSummary(eventid);
                        timeline();
                    })
                    .catch(showError);
            }
        });

        makeAdapter(
            region,
            config.source
        ).then(a => {

            adapter = a;

            duration =
                adapter.getDuration() || 0;

            if (
                config.resumeplayback &&
                Number(config.lastposition) > 1
            ) {
                adapter.seek(
                    Number(config.lastposition)
                );
            }

            timeline();

            setInterval(() => {
                if (!adapter) {
                    return;
                }

                duration =
                    adapter.getDuration() ||
                    duration;

                const current =
                    adapter.getCurrentTime();

                if (adapter.isPlaying()) {
                    if (
                        previousTime !== null &&
                        current >= previousTime &&
                        (
                            current - previousTime
                        ) <= 3
                    ) {
                        if (pendingStart === null) {
                            pendingStart =
                                previousTime;
                        }

                        pendingEnd = current;
                    }

                    if (
                        Date.now() - lastFlush >=
                        10000
                    ) {
                        flushProgress();
                    }

                } else if (
                    pendingStart !== null
                ) {
                    flushProgress();
                }

                previousTime = current;

            }, 1000);

            window.addEventListener(
                'beforeunload',
                flushProgress
            );

            document.addEventListener(
                'visibilitychange',
                () => {
                    if (document.hidden) {
                        flushProgress();
                    }
                }
            );

        }).catch(showError);
    };

    return {
        init: init
    };
});
