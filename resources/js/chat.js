/**
 * Customer chat on the tracking page.
 *
 * New messages are collected by polling a small JSON endpoint, which keeps the
 * feature working on shared hosting with no websocket server. Swapping in a
 * broadcast listener later only means replacing the fetch below.
 *
 * A conversation is deleted once it is past the retention window. The endpoint
 * answers 404 from that moment, and the page takes the thread off the screen
 * rather than leaving a copy of it sitting in the browser.
 */
export default function trackingChat({ endpoint, interval = 8000, lastId = 0 }) {
    return {
        endpoint,
        interval,
        lastId,
        messages: [],
        polling: false,
        failures: 0,
        cleared: false,
        timer: null,

        init() {
            this.scrollToEnd();
            this.schedule();

            document.addEventListener('visibilitychange', () => {
                if (! document.hidden) {
                    this.poll();
                }
            });
        },

        schedule() {
            this.timer = window.setInterval(() => {
                if (! document.hidden) {
                    this.poll();
                }
            }, this.interval);
        },

        // The conversation is gone. Clear what is on screen and stop polling.
        clear() {
            this.cleared = true;
            this.messages = [];

            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },

        async poll() {
            if (this.polling || this.cleared) {
                return;
            }

            this.polling = true;

            try {
                const response = await fetch(`${this.endpoint}?after=${this.lastId}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.status === 404) {
                    this.clear();

                    return;
                }

                if (! response.ok) {
                    throw new Error(`Unexpected response: ${response.status}`);
                }

                const payload = await response.json();
                this.failures = 0;

                payload.messages.forEach((message) => {
                    if (message.id > this.lastId) {
                        this.lastId = message.id;
                        this.messages.push(message);
                    }
                });

                if (payload.messages.length) {
                    this.$nextTick(() => this.scrollToEnd());
                }
            } catch (error) {
                this.failures += 1;

                // Back off after repeated failures rather than hammering the server.
                if (this.failures > 3) {
                    this.interval = Math.min(this.interval * 2, 60000);
                }
            } finally {
                this.polling = false;
            }
        },

        scrollToEnd() {
            const thread = this.$refs.thread;

            if (thread) {
                thread.scrollTop = thread.scrollHeight;
            }
        },
    };
}
