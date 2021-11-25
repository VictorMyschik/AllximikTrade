<li class="nav-item m-l-15 border mr-border-radius-10 border-gray">
  <a class="nav-link mr-nav-link-color" href="/"
     onclick="if(confirm('Выйти?')){event.preventDefault(); document.getElementById('logout-form').submit();}else {return false;}">
    <i class="fa fa-sign-out-alt"></i>
  </a>
  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
  </form>
</li>