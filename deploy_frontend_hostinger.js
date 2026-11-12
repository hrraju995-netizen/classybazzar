const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

const conn = new Client();
const LOCAL_NEXT = 'c:\\laragon\\www\\classybazar\\client\\.next';
const REMOTE_NEXT = '/home/u174620650/domains/classybazarbd.com/nextjs_client/.next';
const SKIP_DIRS = new Set(['dev', 'cache', 'trace', 'trace-build', 'diagnostics']);

let uploaded = 0;
let failed = 0;

function uploadDir(sftp, localDir, remoteDir, cb) {
  const entries = fs.readdirSync(localDir, { withFileTypes: true });
  let pending = entries.length;
  if (pending === 0) return cb();

  entries.forEach((entry) => {
    const localPath = path.join(localDir, entry.name);
    const remotePath = `${remoteDir}/${entry.name}`;
    
    if (entry.isDirectory()) {
      if (SKIP_DIRS.has(entry.name)) {
        pending--;
        if (pending === 0) cb();
        return;
      }
      sftp.mkdir(remotePath, () => {
        uploadDir(sftp, localPath, remotePath, () => {
          pending--;
          if (pending === 0) cb();
        });
      });
    } else {
      sftp.fastPut(localPath, remotePath, (err) => {
        if (err) {
          failed++;
          console.error(`FAIL: ${remotePath} - ${err.message}`);
        } else {
          uploaded++;
          if (uploaded % 50 === 0) console.log(`Uploaded ${uploaded} files...`);
        }
        pending--;
        if (pending === 0) cb();
      });
    }
  });
}

conn.on('ready', () => {
  console.log('SSH Connected! Uploading fresh .next build to Hostinger...');
  conn.sftp((err, sftp) => {
    if (err) { console.error('SFTP error:', err); conn.end(); return; }

    uploadDir(sftp, LOCAL_NEXT, REMOTE_NEXT, () => {
      console.log(`\nUpload complete! ${uploaded} files uploaded, ${failed} failed.`);
      restartServer();
    });
  });
}).connect({
  host: '217.21.87.110', port: 65002,
  username: 'u174620650', password: 'Classybazzar@1919',
  readyTimeout: 20000,
});

function restartServer() {
  console.log('Restarting Next.js server on Hostinger...');
  conn.shell((err, stream) => {
    if (err) throw err;
    stream.on('data', d => process.stdout.write(d.toString()));
    stream.on('close', () => {
      console.log('Server restart completed!');
      conn.end();
    });

    stream.write('pkill -9 -f server.js\n');
    stream.write('rm -f /home/u174620650/domains/classybazarbd.com/nextjs_client/next.sock /tmp/classybazar_start.lock\n');
    stream.write('nohup bash /home/u174620650/domains/classybazarbd.com/nextjs_client/start_next.sh > /home/u174620650/domains/classybazarbd.com/nextjs_client/next-server.log 2>&1 < /dev/null &\n');
    stream.write('sleep 5\n');
    stream.write('cat /home/u174620650/domains/classybazarbd.com/nextjs_client/next-server.log\n');
    stream.write('ls -la /home/u174620650/domains/classybazarbd.com/nextjs_client/next.sock\n');
    stream.write('exit\n');
  });
}
