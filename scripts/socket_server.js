const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');

const port = Number(process.env.SOCKET_PORT || 3001);
const pollMs = Number(process.env.SOCKET_POLL_MS || 1000);

const dbConfig = {
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'hems_db',
  waitForConnections: true,
  connectionLimit: Number(process.env.DB_CONNECTION_LIMIT || 5),
};

const server = http.createServer();
const io = new Server(server, {
  cors: {
    origin: process.env.SOCKET_CORS_ORIGIN || '*',
    methods: ['GET', 'POST'],
  },
});

const pool = mysql.createPool(dbConfig);

io.on('connection', (socket) => {
  const userId = Number(socket.handshake.auth && socket.handshake.auth.userId);
  if (!userId) {
    socket.disconnect(true);
    return;
  }

  socket.join(`user:${userId}`);
  socket.emit('socket.ready', { userId });
});

async function emitPendingEvents() {
  let connection;
  try {
    connection = await pool.getConnection();
    const [events] = await connection.query(
      `SELECT id, user_id, event_name, payload_json
       FROM notification_realtime_events
       WHERE status = 'pending'
       ORDER BY created_at ASC
       LIMIT 100`
    );

    for (const event of events) {
      let payload = {};
      try {
        payload = typeof event.payload_json === 'string' ? JSON.parse(event.payload_json) : event.payload_json;
      } catch (error) {
        payload = { raw: event.payload_json };
      }

      io.to(`user:${event.user_id}`).emit(event.event_name, payload);
      await connection.query(
        "UPDATE notification_realtime_events SET status='delivered', attempts=attempts+1, delivered_at=NOW() WHERE id=?",
        [event.id]
      );
    }
  } catch (error) {
    if (connection) {
      await connection.query(
        "UPDATE notification_realtime_events SET status='failed', attempts=attempts+1, last_error=? WHERE status='pending' ORDER BY created_at ASC LIMIT 1",
        [error.message]
      ).catch(() => {});
    }
    console.error('[socket] poll failed:', error.message);
  } finally {
    if (connection) connection.release();
  }
}

setInterval(emitPendingEvents, pollMs);

server.listen(port, () => {
  console.log(`HEMS Socket.IO notification bridge listening on :${port}`);
});
