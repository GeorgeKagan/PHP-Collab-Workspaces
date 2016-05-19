<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PostToWorkspaceCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:posttoworkspace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post to workspace from email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $fd = fopen('php://stdin', 'r');
        $raw = '';
        while (!feof($fd)) {
            $raw .= fread($fd, 1024);
        }
        fclose($fd);

        $params['include_bodies'] = true;
        $params['decode_bodies'] = true;
        $params['decode_headers'] = true;
        $decoder = new Mail_mimeDecode($raw);
        $structure = $decoder->decode($params);

        $to = $structure->headers['to'];
        $from = $structure->headers['from'];

        $plain = '';
        $html = '';
        $attachments = array();

        foreach ($structure->parts as $part) {
            if (isset($part->disposition) && ($part->disposition === 'attachment')) {
                $attachment[] = array(
                    'body' => $part->body,
                    'name' => $part->ctype_parameters['name']
                );
            } else {
                if (isset($part->parts) && (count($part->parts) > 0)) {
                    foreach ($part->parts as $sp) {
                        if (strpos($sp->headers['content-type'], 'text/plain') !== false)
                            $plain = $sp->body;
                        if (strpos($sp->headers['content-type'], 'text/html') !== false)
                            $html = $sp->body;
                    }
                } else {
                    if (strpos($part->headers['content-type'], 'text/plain') !== false)
                        $plain = $part->body;
                    if (strpos($part->headers['content-type'], 'text/html') !== false)
                        $html = $part->body;
                }
            }
        }

        if (trim($plain) == '' && isset($structure->body)) $plain = $structure->body;
        if (trim($html) == '') $html = nl2br($plain);
        if (trim($html) == '') return; //empty body message

        if ((strpos($to, '<') !== false) && (strpos($to, '>') !== false)) {
            $matches = array();
            preg_match('/\<(.*)\@(.*)\>/', $to, $matches);
            $hash = @$matches[0];
        } else {
            list($hash, $rest) = explode('@', $to);
        }

        if (is_null($hash) || empty($hash)) return;

        if ((strpos($from, '<') !== false) && (strpos($from, '>') !== false)) {
            $matches = array();
            preg_match('/\<(.*)\>/', $from, $matches);
            $from = @$matches[0];
        }

        if (is_null($from) || empty($from)) return;

        $user = Db::table('users')->select('id')->where('email', '=', $from)->first();
        if (is_null($user) || !$user) return; //no such user

        $group = DB::table('groups')->select('id')->where('hash', '=', $hash)->first();
        if (is_null($group) || !$group) return; //no such group

        $userGroup = Db::table('user2group')->select('user_id')->where('user_id', '=', $user->id)->where('group_id', '=', $group->id)->first();
        if (is_null($userGroup) || !$userGroup) return; //user is not part of of the group

        $post = new Post;
        $post->user_id = $user->id;
        $post->group_id = $group->id;
        $post->body = $html;
        $post->label = 'homework';
        $post->save();

        Event::fire('post.create', array($post));

        $filesystem = new Illuminate\Filesystem\Filesystem;
        $index = 1;
        foreach ($attachments as $attachment) {
            $ext = pathinfo($attachment['name'], PATHINFO_EXTENSION);

            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) continue;

            $filename = '/user_content/post_images/' . $index . '-' . time() . '.' . $ext;
            $filesystem->put(public_path() . $filename, $attachment['body']);
            $index++;

            $PostImage = new PostImage();
            $PostImage->post_id = $post->id;
            $PostImage->img_url = $filename;
            $PostImage->annotations = json_encode([]);
            $PostImage->save();
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('example', InputArgument::REQUIRED, 'An example argument.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
        );
    }

}