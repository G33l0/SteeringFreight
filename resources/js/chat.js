/**
 * Customer chat on the tracking page.
 *
 * New messages are collected by polling a small JSON endpoint, which keeps the
 * feature working on shared hosting with no websocket server. Swapping in a
 * broadcast listener later only means replacing the fetch below.
 */
export default function trackingChat({ endpoint, interval = 8000, lastId = 0 }) {
    return {
        endpoint,
        interval,
        lastId,
        messages: [],
        polling: false,
        failures: 0,

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
            window.setInterval(() => {
                if (! document.hidden) {
                    this.poll();
                }
            }, this.interval);
        },

        async poll() {
            if (this.polling) {
                return;
            }

            this.polling = true;

            try {
                const response = await fetch(`${this.endpoint}?after=${this.lastId}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

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
