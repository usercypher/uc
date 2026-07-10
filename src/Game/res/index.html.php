<?php
$app = $data['app'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Example - Game</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;overflow:hidden;touch-action:none;}
body{
    background:#111;
    color:#fff;
    font-family:sans-serif;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    height:100vh;
}
#info{
    font-size:14px;
    margin-bottom:6px;
    width:340px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
#info a {
    color: #4caf50;
    text-decoration: none;
    font-weight: bold;
    font-size: 13px;
    border: 1px solid #4caf50;
    padding: 2px 8px;
    border-radius: 4px;
    transition: background 0.2s;
}
#info a:hover {
    background: rgba(76, 175, 80, 0.2);
}
.canvas-container{
    position: relative;
    width:340px;
    height:340px;
}
canvas{
    background:#222;
    border:2px solid #555;
    width:100%;
    height:100%;
    image-rendering:pixelated;
}
#server-alert {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    text-align: center;
    box-sizing: border-box;
    font-size: 15px;
    color: #ff5252;
    font-weight: bold;
    line-height: 1.4;
    border: 2px solid #555;
}
#ctrls{
    display:flex;
    width:340px;
    justify-content:space-between;
    margin-top:8px;
}
.grid{
    display:grid;
    grid-template-columns:repeat(3,40px);
    grid-gap:5px;
}
button{
    width:40px;
    height:40px;
    background:#444;
    color:#fff;
    border:none;
    font-weight:bold;
    border-radius:4px;
    user-select:none;
}
#fire{
    width:80px;
    height:85px;
    background:#d32f2f;
    border-radius:8px;
    font-size:16px;
}
#retry{
    display:none;
    width:340px;
    height:45px;
    margin-top:10px;
    background:#2e7d32;
    font-size:18px;
}
</style>
</head>
<body>

<div id="info">
    <div>Lives: <span id="hp">3</span></div>
    <a href="<?= $app->url('ROUTE', '') ?>">Home</a>
</div>

<div class="canvas-container">
    <canvas id="g" width="400" height="400"></canvas>
    <div id="server-alert">WebSocket offline. Run ./uc-hub.sh or uc-hub.bat on windows to start websocket, also run './cli.sh db print --name="GAME" | ./cli.sh db exec --name="GAME"' or 'cli.bat db print --name="GAME" | cli.bat db exec --name="GAME"' on windows to prepare db</div>
</div>

<div id="ctrls">
    <div class="grid">
        <div></div><button id="u">▲</button><div></div>
        <button id="l">◀</button><div></div><button id="r">▶</button>
        <div></div><button id="d">▼</button><div></div>
    </div>

    <button id="fire">FIRE</button>
</div>

<button id="retry">RETRY</button>

<script>
const canvas=document.getElementById('g');
const ctx=canvas.getContext('2d');
const serverAlert=document.getElementById('server-alert');

let ws;
let myId=null;
let allPlayers={};
let keys={up:false,down:false,left:false,right:false};
let lastseen="";

function connect(){

    ws=new WebSocket('ws://'+window.location.hostname+':2080');

    ws.onopen=()=>{
        serverAlert.style.display='none';
    };

    ws.onmessage=(e)=>{

        const data=JSON.parse(e.data);

        if(data.type==='init'){
            myId=String(data.id);
            document.getElementById('retry').style.display='none';
        }

        if(data.type==='state'){

            allPlayers=data.players;

            if(myId&&allPlayers[myId]){
                const hp=parseInt(allPlayers[myId].lives);
                document.getElementById('hp').innerText=hp;

                if(hp<=0){
                    document.getElementById('retry').style.display='block';
                }
            }

        }

    };

    ws.onclose=()=>{
        serverAlert.style.display='flex';
    };

    ws.onerror=()=>{
        serverAlert.style.display='flex';
    };

}

connect();

setInterval(()=>{

    if(!ws||ws.readyState!==WebSocket.OPEN||!myId)return;

    let state=JSON.stringify({
        type:'input',
        keys:keys
    });

    if(state===lastseen){
        state=JSON.stringify({});
    }else{
        lastseen=state;
    }

    ws.send(state);

},1000/30);

const bind=(id,k)=>{
    const el=document.getElementById(id);

    el.addEventListener('touchstart',e=>{
        e.preventDefault();
        keys[k]=true;
    },{passive:false});

    el.addEventListener('touchend',e=>{
        e.preventDefault();
        keys[k]=false;
    },{passive:false});
};

bind('u','up');
bind('d','down');
bind('l','left');
bind('r','right');

const fireBtn=document.getElementById('fire');

fireBtn.addEventListener('touchstart',e=>{
    e.preventDefault();
    if(ws&&ws.readyState===WebSocket.OPEN){
        ws.send(JSON.stringify({type:'fire'}));
    }
},{passive:false});

window.addEventListener('keydown',e=>{

    if(e.key==='ArrowUp'||e.key==='w')keys.up=true;
    if(e.key==='ArrowDown'||e.key==='s')keys.down=true;
    if(e.key==='ArrowLeft'||e.key==='a')keys.left=true;
    if(e.key==='ArrowRight'||e.key==='d')keys.right=true;

    if(e.key===' '){
        if(ws&&ws.readyState===WebSocket.OPEN){
            ws.send(JSON.stringify({type:'fire'}));
        }
    }

});

window.addEventListener('keyup',e=>{

    if(e.key==='ArrowUp'||e.key==='w')keys.up=false;
    if(e.key==='ArrowDown'||e.key==='s')keys.down=false;
    if(e.key==='ArrowLeft'||e.key==='a')keys.left=false;
    if(e.key==='ArrowRight'||e.key==='d')keys.right=false;

});

document.getElementById('retry').onclick=()=>{

    if(ws){
        ws.close();
    }

    myId=null;
    allPlayers={};
    lastseen="";
    keys={up:false,down:false,left:false,right:false};

    document.getElementById('hp').innerText="3";
    document.getElementById('retry').style.display='none';

    connect();

};

function draw(){

    ctx.clearRect(0,0,400,400);

    Object.keys(allPlayers).forEach(id=>{

        const pl=allPlayers[id];

        if(parseInt(pl.lives)<=0){

            if(String(id)===myId){
                ctx.fillStyle='red';
                ctx.font='30px Arial';
                ctx.fillText("GAME OVER",110,200);
            }

            return;
        }

        ctx.fillStyle=pl.color||'#fff';
        ctx.fillRect(+pl.x,+pl.y,20,20);

        if(String(id)===myId){
            ctx.strokeStyle='#fff';
            ctx.lineWidth=2;
            ctx.strokeRect(+pl.x,+pl.y,20,20);
        }

        ctx.fillStyle='#fff';

        if(Array.isArray(pl.bullets)){
            pl.bullets.forEach(b=>{
                ctx.fillRect(+b.x,+b.y,5,5);
            });
        }

    });

}

// Clean up WebSocket connection explicitly when leaving the page or clicking "Home"
window.addEventListener('beforeunload', () => {
    if (ws) {
        // Clear standard event handlers to prevent error notices during component unmounting
        ws.onclose = null;
        ws.onerror = null;
        ws.close();
    }
});

(function loop(){
    draw();
    requestAnimationFrame(loop);
})();
</script>

</body>
</html>