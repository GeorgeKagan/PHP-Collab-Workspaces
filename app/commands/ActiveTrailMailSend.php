<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ActiveTrailMailSend extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:activetrail';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sending email to users via activetrail';

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
        require_once public_path()."/../ActiveTrail_Class.php";
        $activetrail = new Active_Trail('leonliber@gmail.com', 'fmpq40ji', '');
        $mails = DB::table('mail_queue')->get();
        $ids = [];
        foreach ($mails as $mail){
            $activetrail->SendMessageToEmails('leonliber@gmail.com', 'AfterClass',$mail->to, '', $mail->subject, $mail->content);
            $ids[] = $mail->id;
        }
        $max = max($ids);
        DB::table('mail_queue')->where('id','<=',$max)->delete();
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
