<div class="typography">
	<% if Menu(2) %>
		<% include SideBar %>
		<div id="Content">
	<% end_if %>
			
	<% if Level(2) %>
	  	<% include BreadCrumbs %>
	<% end_if %>
		<h1 class="heading">$Title</h1>
		$Content
		$Form
		<% if Data %>
		<ul>
		<% control Data %>
			<li>Name : $Name - Score : $Score</li>
		<% end_control %>
		</ul>
		<a href="$Link">back</a>
		<% end_if %>
		$PageComments
	
	<% if Menu(2) %>
		</div>
	<% end_if %>
</div>
