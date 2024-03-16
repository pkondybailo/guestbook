import '../assets/styles/app.scss';

import { h, render } from 'preact';
import {Link, Router} from 'preact-router';
import htm from 'htm';

import Home from './pages/home';
import Conference from './pages/conference';

const html = htm.bind(h);

function App() {
  return (
    <div>
      <header className="header">
        <nav className="navbar navbar-light bg-light">
          <div className="container">
            <Link href="/" className="navbar-brand mr-4 pr-2">
              &#128217; Guestbook
            </Link>
          </div>
        </nav>
        <nav className="navbar navbar-light bg-light">
          <div className="container">
            <Link href="/conference/amsterdan2019" className="navbar-brand mr-4 pr-2">
              Amsterdam 2019
            </Link>
          </div>
        </nav>
      </header>

      <Router>
        <Home path="/" />
        <Conference path="/conference/:slug" />
      </Router>
    </div>
  );
}

render(html`<${App} />`, document.getElementById('app'));
