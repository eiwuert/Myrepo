<?php
class PriorityServerListTest extends PHPUnit_Framework_TestCase
{
	public function testGet()
	{
		$priority_server_1 = PriorityServer::newInstance('mail.example.com', 25, 1);
		$priority_server_5 = PriorityServer::newInstance('mail.example.com', 25, 5);
		 
		$list = new PriorityServerList();
		$list->add($priority_server_1);
		$list->add($priority_server_5);
		
		$this->assertEquals($priority_server_5, $list->get(5));
	}
	
	public function testGetOutOfOrder()
	{
		$priority_server_1 = PriorityServer::newInstance('mail.example.com', 25, 1);
		$priority_server_5 = PriorityServer::newInstance('mail.example.com', 25, 5);
		 
		$list = new PriorityServerList();
		$list->add($priority_server_5);
		$list->add($priority_server_1);
		
		$this->assertEquals($priority_server_1->getMinimumPriority(), $list->get(1)->getMinimumPriority());
	}
	
	public function testGetNoServerFound()
	{
		$priority_server_1 = PriorityServer::newInstance('mail.example.com', 25, 1);
		$priority_server_5 = PriorityServer::newInstance('mail.example.com', 25, 5);
		 
		$list = new PriorityServerList();
		$list->add($priority_server_5);
		$list->add($priority_server_1);
		
		$this->assertFalse($list->get(20));
	}
}