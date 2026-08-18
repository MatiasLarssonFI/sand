(function () {
  const boot = window.CALENDAR_APP;
  const csrfToken = boot.csrfToken;
  const state = {
    currentUserId: boot.currentUserId,
    calendarId: boot.state.selectedCalendar ? boot.state.selectedCalendar.id : null,
    view: boot.state.view ? boot.state.view.type : 'month',
    date: boot.state.view ? boot.state.view.start : new Date().toISOString().slice(0, 10),
    weeks: boot.defaultWeeks,
    payload: boot.state,
  };

  const elements = {
    userSwitcher: document.getElementById('user-switcher'),
    calendarSelect: document.getElementById('calendar-select'),
    viewSelect: document.getElementById('view-select'),
    weeksControl: document.getElementById('weeks-control'),
    weeksInput: document.getElementById('weeks-input'),
    viewLabel: document.getElementById('view-label'),
    grid: document.getElementById('calendar-grid'),
    memberPanel: document.getElementById('members-panel'),
    memberForm: document.getElementById('member-form'),
    memberUser: document.getElementById('member-user'),
    memberRole: document.getElementById('member-role'),
    addEventButton: document.getElementById('add-event-button'),
    eventForm: document.getElementById('event-form'),
    deleteEventButton: document.getElementById('delete-event-button'),
    resetEventButton: document.getElementById('reset-event-button'),
    flashMessage: document.getElementById('flash-message'),
    previousButton: document.getElementById('previous-range'),
    nextButton: document.getElementById('next-range'),
    todayButton: document.getElementById('today-range'),
    eventId: document.getElementById('event-id'),
    eventTitle: document.getElementById('event-title'),
    eventDescription: document.getElementById('event-description'),
    eventStart: document.getElementById('event-start'),
    eventEnd: document.getElementById('event-end'),
  };

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showMessage(message, isError = false) {
    elements.flashMessage.textContent = message;
    elements.flashMessage.classList.toggle('error', isError);
  }

  async function request(url, options = {}) {
    const response = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
        ...(options.headers || {}),
      },
      ...options,
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.error || 'Request failed.');
    }

    return payload;
  }

  function currentDate() {
    return state.date || new Date().toISOString().slice(0, 10);
  }

  function shiftDate(step) {
    const date = new Date(`${currentDate()}T00:00:00`);
    const delta = state.view === 'day' ? 1 : state.view === 'week' ? 7 : state.view === 'month' ? 31 : state.weeks * 7;
    date.setDate(date.getDate() + (delta * step));
    state.date = date.toISOString().slice(0, 10);
  }

  function groupEvents(events) {
    const groups = {};
    events.forEach((event) => {
      groups[event.startDate] = groups[event.startDate] || [];
      groups[event.startDate].push(event);
    });
    return groups;
  }

  function renderCalendar() {
    const payload = state.payload;
    const days = payload.view ? payload.view.days : [];

    elements.viewLabel.textContent = payload.view ? payload.view.label : 'No calendar';
    elements.grid.innerHTML = '';

    if (!payload.selectedCalendar || !payload.view) {
      elements.grid.innerHTML = '<div class="empty-state">Import the schema and sign in as a seeded user to start using the calendar.</div>';
      return;
    }

    const groupedEvents = groupEvents(payload.events);
    const columns = state.view === 'day' ? 1 : 7;
    elements.grid.style.setProperty('--columns', String(columns));

    const header = document.createElement('div');
    header.className = 'grid-header';
    const body = document.createElement('div');
    body.className = 'grid-body';

    days.forEach((day) => {
      const headerCell = document.createElement('div');
      headerCell.innerHTML = `<strong>${escapeHtml(day.weekday)}</strong><br><small>${escapeHtml(day.day)} ${escapeHtml(day.month)}</small>`;
      header.appendChild(headerCell);

      const card = document.createElement('div');
      card.className = `day-card${day.primary ? '' : ' muted'}`;
      const events = groupedEvents[day.date] || [];
      card.innerHTML = `
        <div><strong>${escapeHtml(day.date)}</strong></div>
        <div class="event-list">
          ${events.map((event) => `
            <button type="button" class="event-chip" data-event-id="${event.id}">
              ${escapeHtml(event.title)}
              <small>${escapeHtml(event.timeLabel)}</small>
            </button>
          `).join('')}
        </div>
      `;
      body.appendChild(card);
    });

    elements.grid.appendChild(header);
    elements.grid.appendChild(body);
  }

  function renderMembers() {
    const payload = state.payload;
    const canManage = payload.permissions && payload.permissions.manage;
    const selectedUserIds = new Set((payload.members || []).map((member) => member.userId));

    elements.memberPanel.innerHTML = (payload.members || []).map((member) => `
      <div class="member-row">
        <div class="member-meta">
          <strong>${escapeHtml(member.name)}</strong>
          <small>${escapeHtml(member.email)}</small>
        </div>
        <select data-member-role="${member.id}" ${canManage ? '' : 'disabled'}>
          ${['viewer', 'editor', 'owner'].map((role) => `
            <option value="${role}" ${member.role === role ? 'selected' : ''}>${role}</option>
          `).join('')}
        </select>
        <button type="button" class="secondary" data-remove-member="${member.id}" ${canManage ? '' : 'disabled'}>Remove</button>
      </div>
    `).join('') || '<p class="empty-state">No members yet.</p>';

    elements.memberUser.innerHTML = (payload.users || [])
      .filter((user) => !selectedUserIds.has(user.id))
      .map((user) => `<option value="${user.id}">${escapeHtml(user.name)}</option>`)
      .join('');

    elements.memberForm.style.display = canManage ? 'grid' : 'none';
  }

  function renderSelectors() {
    const payload = state.payload;
    elements.calendarSelect.innerHTML = (payload.calendars || []).map((calendar) => `
      <option value="${calendar.id}" ${payload.selectedCalendar && payload.selectedCalendar.id === calendar.id ? 'selected' : ''}>
        ${escapeHtml(calendar.name)}
      </option>
    `).join('');
    elements.viewSelect.value = state.view;
    elements.weeksInput.value = String(state.weeks);
    elements.weeksControl.style.display = state.view === 'n-weeks' ? 'grid' : 'none';
    const canEdit = payload.permissions && payload.permissions.edit;
    elements.addEventButton.disabled = !canEdit;
    elements.eventForm.querySelectorAll('input, textarea, button').forEach((field) => {
      if (field.id === 'reset-event-button') {
        return;
      }
      field.disabled = !canEdit;
    });
  }

  function resetEventForm() {
    elements.eventId.value = '';
    elements.eventTitle.value = '';
    elements.eventDescription.value = '';
    elements.eventStart.value = '';
    elements.eventEnd.value = '';
    elements.deleteEventButton.style.display = 'none';
  }

  function fillEventForm(event) {
    elements.eventId.value = String(event.id || '');
    elements.eventTitle.value = event.title || '';
    elements.eventDescription.value = event.description || '';
    elements.eventStart.value = event.startInput || '';
    elements.eventEnd.value = event.endInput || '';
    elements.deleteEventButton.style.display = event.id ? 'inline-flex' : 'none';
  }

  async function loadCalendar() {
    if (!state.calendarId) {
      render();
      return;
    }

    const params = new URLSearchParams({
      calendar_id: String(state.calendarId),
      view: state.view,
      date: state.date,
      weeks: String(state.weeks),
    });

    state.payload = await request(`/api/calendar?${params.toString()}`, { headers: {} });
    state.calendarId = state.payload.selectedCalendar ? state.payload.selectedCalendar.id : null;
    render();
  }

  function render() {
    renderSelectors();
    renderCalendar();
    renderMembers();
  }

  elements.userSwitcher.addEventListener('change', async (event) => {
    await request('/api/switch-user', {
      method: 'POST',
      body: JSON.stringify({ userId: event.target.value }),
    });
    state.currentUserId = Number(event.target.value);
    await loadCalendar();
    showMessage('User switched.');
  });

  elements.calendarSelect.addEventListener('change', async (event) => {
    state.calendarId = Number(event.target.value);
    await loadCalendar();
  });

  elements.viewSelect.addEventListener('change', async (event) => {
    state.view = event.target.value;
    await loadCalendar();
  });

  elements.weeksInput.addEventListener('change', async (event) => {
    state.weeks = Math.max(2, Number(event.target.value || boot.defaultWeeks));
    await loadCalendar();
  });

  elements.previousButton.addEventListener('click', async () => {
    shiftDate(-1);
    await loadCalendar();
  });

  elements.nextButton.addEventListener('click', async () => {
    shiftDate(1);
    await loadCalendar();
  });

  elements.todayButton.addEventListener('click', async () => {
    state.date = new Date().toISOString().slice(0, 10);
    await loadCalendar();
  });

  elements.addEventButton.addEventListener('click', () => {
    resetEventForm();
    const firstDay = state.payload.view && state.payload.view.days.length ? state.payload.view.days[0].date : new Date().toISOString().slice(0, 10);
    elements.eventStart.value = `${firstDay}T09:00`;
    elements.eventEnd.value = `${firstDay}T10:00`;
  });

  elements.resetEventButton.addEventListener('click', () => {
    resetEventForm();
    showMessage('Event form cleared.');
  });

  elements.eventForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = elements.eventId.value;
    const payload = {
      calendarId: state.calendarId,
      title: elements.eventTitle.value,
      description: elements.eventDescription.value,
      start: elements.eventStart.value,
      end: elements.eventEnd.value,
    };

    try {
      await request(id ? `/api/events/${id}` : '/api/events', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      resetEventForm();
      await loadCalendar();
      showMessage(id ? 'Event updated.' : 'Event created.');
    } catch (error) {
      showMessage(error.message, true);
    }
  });

  elements.deleteEventButton.addEventListener('click', async () => {
    if (!elements.eventId.value) {
      return;
    }

    try {
      await request(`/api/events/${elements.eventId.value}`, { method: 'DELETE' });
      resetEventForm();
      await loadCalendar();
      showMessage('Event deleted.');
    } catch (error) {
      showMessage(error.message, true);
    }
  });

  elements.grid.addEventListener('click', (event) => {
    const button = event.target.closest('[data-event-id]');

    if (!button) {
      return;
    }

    const selected = state.payload.events.find((item) => Number(item.id) === Number(button.dataset.eventId));

    if (selected) {
      fillEventForm(selected);
    }
  });

  elements.memberForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      await request('/api/memberships', {
        method: 'POST',
        body: JSON.stringify({
          calendarId: state.calendarId,
          userId: elements.memberUser.value,
          role: elements.memberRole.value,
        }),
      });
      await loadCalendar();
      showMessage('Member added.');
    } catch (error) {
      showMessage(error.message, true);
    }
  });

  elements.memberPanel.addEventListener('change', async (event) => {
    const memberId = event.target.dataset.memberRole;

    if (!memberId) {
      return;
    }

    try {
      await request(`/api/memberships/${memberId}`, {
        method: 'POST',
        body: JSON.stringify({ role: event.target.value }),
      });
      await loadCalendar();
      showMessage('Member role updated.');
    } catch (error) {
      showMessage(error.message, true);
    }
  });

  elements.memberPanel.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-remove-member]');

    if (!button) {
      return;
    }

    try {
      await request(`/api/memberships/${button.dataset.removeMember}`, { method: 'DELETE' });
      await loadCalendar();
      showMessage('Member removed.');
    } catch (error) {
      showMessage(error.message, true);
    }
  });

  resetEventForm();
  render();
})();
