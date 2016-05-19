<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DeleteUnnecessaryCommentImage extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:deleteimage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete image what not connected with comment';

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
	 * @return mixed
	 */
	public function fire()
	{
		$files = scandir(public_path().'/user_content/comment_images');
        $images = DB::table('post_comments')->lists('img');
        $file_path = public_path().'/user_content/comment_images';
        foreach($files as $file){
            if($file!== '.' and $file!=='..' and !in_array($file,$images)){
                $del = $file_path.'/'.$file;
                unlink($del);
            }
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
			array('example', InputArgument::OPTIONAL, 'An example argument.'),
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
