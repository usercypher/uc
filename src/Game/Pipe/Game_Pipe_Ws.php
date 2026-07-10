<?php

class Game_Pipe_Ws {

    private $app,$curl,$playerRepo,$tickRepo;
    private $server,$token;

    function args($args){
        list(
            $this->app,
            $this->curl,
            $this->playerRepo,
            $this->tickRepo
        )=$args;
    }

    function process($in,$out){
        $type=$in->header['x-uc-hub-type']??'';
        $id=$in->header['x-uc-hub-client']??'';
        $this->server=$in->header['x-uc-hub-server']??'';
        $this->token=$in->header['x-uc-hub-token']??'';
        $body=$in->io(0,"\n");

        if(!$type||!$id){
            $out->code=400;
            return [$in,$out,true];
        }

        if($type==='open') $this->open($id);
        elseif($type==='message'){
            $this->input($id,$body);
            $this->tick();
        }
        elseif($type==='close') $this->close($id);

        return [$in,$out,true];
    }

    private function tick(){

        $fps=60;
        $step=1/$fps;

        $t=$this->tickRepo->one('WHERE id=?',[1],'last_tick_time');
        $last=(float)($t['last_tick_time']??0);
        $now=microtime(true);

        if($now-$last<$step) return;

        $dt=$last?($now-$last):$step;

        $this->tickRepo->update([
            'id'=>1,
            'last_tick_time'=>$now
        ]);

        $this->update($dt);
    }

    private function open($id){

        $this->playerRepo->insert([
            'id'=>$id,
            'x'=>mt_rand(20,360),
            'y'=>mt_rand(20,360),
            'color'=>'#'.str_pad(dechex(mt_rand(0,16777215)),6,'0',STR_PAD_LEFT),
            'lives'=>3,
            'bullets'=>'[]',
            'last_fire'=>0,
            'dir_x'=>0,
            'dir_y'=>1,
            'keys_state'=>'{"up":false,"down":false,"left":false,"right":false}',
        ]);

        $this->send($id,[
            'type'=>'init',
            'id'=>$id
        ]);

        $this->broadcast();
    }

    private function close($id){
        $this->playerRepo->delete($id);
        $this->broadcast();
    }

    private function input($id,$raw){

        $msg=json_decode($raw,true);
        if(!$msg) {
            return;
        }

        $p=$this->playerRepo->one('WHERE id=?',[$id]);
        if(!$p||$p['lives']<=0) return;

        if(($msg['type']??'')==='input'){

            $k=$msg['keys'];

            $dx=0;
            $dy=0;

            if(!empty($k['left'])) $dx=-1;
            if(!empty($k['right'])) $dx=1;
            if(!empty($k['up'])) $dy=-1;
            if(!empty($k['down'])) $dy=1;

            if($dx||$dy){
                $p['dir_x']=$dx;
                $p['dir_y']=$dy;
            }

            $this->playerRepo->update([
                'id'=>$id,
                'dir_x'=>$p['dir_x'],
                'dir_y'=>$p['dir_y'],
                'keys_state'=>json_encode($k),
            ]);

            return;
        }

        if(($msg['type']??'')==='fire'){

            $cooldown = 0.2;

            $p=$this->playerRepo->one('WHERE id=?',[$id]);
            if (microtime(true) - (float)$p['last_fire'] < $cooldown)
            return;

            $bul=json_decode($p['bullets'],true);
            if(!$bul) $bul=[];

            $bul[]=[
                'x'=>$p['x']+8+$p['dir_x']*18,
                'y'=>$p['y']+8+$p['dir_y']*18,
                'dx'=>$p['dir_x']*210,
                'dy'=>$p['dir_y']*210
            ];

            $this->playerRepo->update([
                'id'=>$id,
                'bullets'=>json_encode($bul),
                'last_fire'=>microtime(true),
            ]);
        }
    }

    private function update($dt){

        $players=$this->playerRepo->all();
        if(!$players) return;

        $speed=120*$dt;
        $state=[];

        foreach($players as $p){

            $k=json_decode($p['keys_state'],true);
            if(!$k) $k=[];

            $x=$p['x'];
            $y=$p['y'];

            if(!empty($k['left'])) $x-=$speed;
            if(!empty($k['right'])) $x+=$speed;
            if(!empty($k['up'])) $y-=$speed;
            if(!empty($k['down'])) $y+=$speed;

            $p['x']=max(0,min(380,$x));
            $p['y']=max(0,min(380,$y));
            $p['bullets_arr']=json_decode($p['bullets'],true)?:[];

            $state[$p['id']]=$p;
        }

        foreach ($state as $ownerId => $owner) {

            if ($owner['lives'] <= 0) continue;

            $keep = [];

            foreach ($owner['bullets_arr'] as $b) {

                $b['x'] += $b['dx'] * $dt;
                $b['y'] += $b['dy'] * $dt;

                if ($b['x'] < 0 || $b['x'] > 400 || $b['y'] < 0 || $b['y'] > 400)
                    continue;

                $hit = false;

                foreach ($state as $targetId => $target) {

                    if ($targetId == $ownerId) continue;
                    if ($target['lives'] <= 0) continue;

                    if (
                        $b['x'] >= $target['x'] &&
                        $b['x'] <= $target['x'] + 20 &&
                        $b['y'] >= $target['y'] &&
                        $b['y'] <= $target['y'] + 20
                    ) {
                        $state[$targetId]['lives'] = max(0, $target['lives'] - 1);
                        $hit = true;
                        break;
                    }
                }

                if (!$hit)
                    $keep[] = $b;
            }

            $state[$ownerId]['bullets'] = json_encode($keep);
        }

        $updatePlayers = [];

        foreach ($state as $p) {
            $updatePlayers[] = ([
                'id'         => $p['id'],
                'x'          => $p['x'],
                'y'          => $p['y'],
                'lives'      => $p['lives'],
                'bullets'    => $p['bullets'],
                'dir_x'      => $p['dir_x'],
                'dir_y'      => $p['dir_y'],
                'keys_state' => $p['keys_state'],
            ]);
        }

        $this->playerRepo->updateBatch($updatePlayers);

        $this->broadcast();
    }

    private function broadcast() {

        $players = $this->playerRepo->all();
        if (!$players) return;

        $ids = [];
        $data = [];

        foreach ($players as $p) {

            $ids[] = $p['id'];

            $data[$p['id']] = [
                'id'       => $p['id'],
                'x'        => (float)$p['x'],
                'y'        => (float)$p['y'],
                'color'    => $p['color'],
                'lives'    => (int)$p['lives'],
                'bullets'  => json_decode($p['bullets'], true) ?: []
            ];
        }

        $this->send($ids, [
            'type' => 'state',
            'players' => $data
        ]);
    }

    private function send($clients, $data)
    {
        if (!is_array($clients))
            $clients = [$clients];

        $this->curl->send($this->server, array(
            'method' => 'POST',
            'header' => array(
                'X-Uc-Hub-Client' => implode(',', $clients),
                'X-Uc-Hub-Token' => $this->token
            ),
            'content' => json_encode($data)
        ));

    }
}