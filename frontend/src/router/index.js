import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('../views/Layout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue')
      },
      {
        path: 'bugs',
        name: 'Bugs',
        component: () => import('../views/bugs/BugList.vue')
      },
      {
        path: 'bugs/new',
        name: 'NewBug',
        component: () => import('../views/bugs/NewBug.vue')
      },
      {
        path: 'bugs/:id',
        name: 'BugDetail',
        component: () => import('../views/bugs/BugDetail.vue')
      },
      {
        path: 'bugs/:id/edit',
        name: 'EditBug',
        component: () => import('../views/bugs/EditBug.vue')
      },
      {
        path: 'kanban',
        name: 'Kanban',
        component: () => import('../views/Kanban.vue')
      },
      {
        path: 'projects',
        name: 'Projects',
        component: () => import('../views/Projects.vue')
      },
      {
        path: 'calendar',
        name: 'Calendar',
        component: () => import('../views/Calendar.vue')
      },
      {
        path: 'gantt',
        name: 'Gantt',
        component: () => import('../views/Gantt.vue')
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('../views/Users.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('../views/Profile.vue')
      },
      {
        path: 'analytics',
        name: 'Analytics',
        component: () => import('../views/Analytics.vue'),
        meta: { requiresAdmin: true }
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const userStr = localStorage.getItem('user');
  let user = null;
  
  if (userStr) {
    try {
      user = JSON.parse(userStr);
    } catch(e) {}
  }

  if (to.meta.requiresAuth && !token) {
    next('/login');
  } else if (to.meta.guest && token) {
    next('/');
  } else if (to.meta.requiresAdmin && (!user || user.role?.name?.toLowerCase() !== 'admin')) {
    next('/');
  } else {
    next();
  }
});

export default router;
