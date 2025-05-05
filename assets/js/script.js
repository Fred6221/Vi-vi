/**
 * SocialConnect - Main JavaScript File
 * Handles client-side functionality for the social media platform
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and popovers
    initializeBootstrapComponents();
    
    // Initialize post interactions
    initializePostInteractions();
    
    // Initialize friend requests
    initializeFriendRequests();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize image previews
    initializeImagePreviews();
    
    // Initialize notifications
    initializeNotifications();
    
    // Initialize message system
    initializeMessageSystem();
});

/**
 * Initialize Bootstrap components
 */
function initializeBootstrapComponents() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Initialize post interactions (reactions, comments, etc.)
 */
function initializePostInteractions() {
    // Reaction button functionality
    document.querySelectorAll('.reaction-button').forEach(function(button) {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const reaction = this.getAttribute('data-reaction');
            addReaction(postId, reaction, this);
        });
    });
    
    // Reaction option functionality
    document.querySelectorAll('.reaction-option').forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the reaction-button click
            const postId = this.getAttribute('data-post-id');
            const reaction = this.getAttribute('data-reaction');
            const reactionButton = this.closest('.reaction-container').querySelector('.reaction-button');
            addReaction(postId, reaction, reactionButton);
        });
    });
    
    // Comment toggle functionality
    document.querySelectorAll('.comment-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const commentsSection = this.closest('.card-footer').querySelector('.comments-section');
            commentsSection.classList.toggle('d-none');
            
            // Focus on comment input when showing comments
            if (!commentsSection.classList.contains('d-none')) {
                commentsSection.querySelector('input[name="content"]').focus();
            }
        });
    });
    
    // Delete post functionality
    document.querySelectorAll('.delete-post').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette publication?')) {
                deletePost(postId);
            }
        });
    });
    
    // Load more posts functionality
    const loadMoreButton = document.getElementById('load-more-posts');
    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function() {
            const offset = parseInt(this.getAttribute('data-offset'));
            loadMorePosts(offset);
        });
    }
}

/**
 * Add or update a reaction for a post
 * 
 * @param {number} postId Post ID
 * @param {string} reactionType Reaction type (like, love, haha, wow, sad, angry)
 * @param {HTMLElement} button Reaction button element
 */
function addReaction(postId, reactionType, button) {
    // Send AJAX request to add reaction
    fetch('actions/add_reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId + '&reaction_type=' + reactionType
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Define reaction types and their icons/colors
            const reactionTypes = {
                'like': { icon: 'fa-thumbs-up', color: 'primary', text: 'J\'aime' },
                'love': { icon: 'fa-heart', color: 'danger', text: 'J\'adore' },
                'haha': { icon: 'fa-laugh', color: 'warning', text: 'Haha' },
                'wow': { icon: 'fa-surprise', color: 'warning', text: 'Wow' },
                'sad': { icon: 'fa-sad-tear', color: 'info', text: 'Triste' },
                'angry': { icon: 'fa-angry', color: 'danger', text: 'Grrr' }
            };
            
            // Update button appearance
            button.classList.remove('reacted', 'reacted-like', 'reacted-love', 'reacted-haha', 'reacted-wow', 'reacted-sad', 'reacted-angry');
            
            if (data.reaction) {
                // Add reaction classes
                button.classList.add('reacted', 'reacted-' + data.reaction);
                
                // Update button text and icon
                const reactionInfo = reactionTypes[data.reaction];
                button.innerHTML = `<i class="fas ${reactionInfo.icon} me-1 text-${reactionInfo.color}"></i> ${reactionInfo.text}`;
                
                // Update data-reaction attribute
                button.setAttribute('data-reaction', data.reaction);
            } else {
                // Reset to default (no reaction)
                button.innerHTML = '<i class="fas fa-thumbs-up me-1 text-secondary"></i> J\'aime';
                button.setAttribute('data-reaction', 'like');
            }
            
            // Update reaction summary
            updateReactionSummary(postId, data.counts);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update the reaction summary display
 * 
 * @param {number} postId Post ID
 * @param {object} counts Reaction counts
 */
function updateReactionSummary(postId, counts) {
    const post = document.getElementById('post-' + postId);
    if (!post) return;
    
    const summaryContainer = post.querySelector('.reaction-summary')?.parentNode;
    if (!summaryContainer) return;
    
    // Define reaction types and their icons/colors
    const reactionTypes = {
        'like': { icon: 'fa-thumbs-up', color: 'primary' },
        'love': { icon: 'fa-heart', color: 'danger' },
        'haha': { icon: 'fa-laugh', color: 'warning' },
        'wow': { icon: 'fa-surprise', color: 'warning' },
        'sad': { icon: 'fa-sad-tear', color: 'info' },
        'angry': { icon: 'fa-angry', color: 'danger' }
    };
    
    if (counts.total > 0) {
        // Create or update reaction summary
        let html = '<div class="reaction-summary">';
        
        // Display reaction icons (up to 3)
        let displayedReactions = 0;
        for (const type in reactionTypes) {
            if (counts[type] > 0 && displayedReactions < 3) {
                displayedReactions++;
                html += `<span class="reaction-icon reaction-${type}">
                            <i class="fas ${reactionTypes[type].icon} text-${reactionTypes[type].color}"></i>
                        </span>`;
            }
        }
        
        // Add reaction count
        html += `<span class="reaction-count">${counts.total}</span>`;
        html += '</div>';
        
        summaryContainer.innerHTML = html;
    } else {
        // No reactions, clear the container
        summaryContainer.innerHTML = '';
    }
}

/**
 * For backward compatibility - toggle like for a post
 * 
 * @param {number} postId Post ID
 * @param {HTMLElement} button Like button element
 */
function toggleLike(postId, button) {
    addReaction(postId, 'like', button);
}

/**
 * Delete a post
 * 
 * @param {number} postId Post ID
 */
function deletePost(postId) {
    // Send AJAX request to delete post
    fetch('actions/delete_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove post from DOM
            const postElement = document.getElementById('post-' + postId);
            postElement.classList.add('fade-out');
            
            setTimeout(function() {
                postElement.remove();
            }, 300);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Load more posts
 * 
 * @param {number} offset Offset for pagination
 */
function loadMorePosts(offset) {
    const loadMoreButton = document.getElementById('load-more-posts');
    loadMoreButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
    loadMoreButton.disabled = true;
    
    // Send AJAX request to load more posts
    fetch('actions/load_more_posts.php?offset=' + offset)
    .then(response => response.text())
    .then(html => {
        if (html.trim()) {
            // Append new posts to news feed
            const newsFeed = document.getElementById('news-feed');
            newsFeed.insertAdjacentHTML('beforeend', html);
            
            // Update offset for next load
            const newOffset = offset + 10;
            loadMoreButton.setAttribute('data-offset', newOffset);
            
            // Re-initialize post interactions for new posts
            initializePostInteractions();
            
            // Reset button
            loadMoreButton.innerHTML = 'Charger plus de publications';
            loadMoreButton.disabled = false;
        } else {
            // No more posts to load
            loadMoreButton.innerHTML = 'Aucune autre publication';
            loadMoreButton.disabled = true;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        loadMoreButton.innerHTML = 'Réessayer';
        loadMoreButton.disabled = false;
    });
}

/**
 * Initialize friend request functionality
 */
function initializeFriendRequests() {
    // Add friend button functionality
    document.querySelectorAll('.add-friend').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            sendFriendRequest(userId, this);
        });
    });
    
    // Accept friend request functionality
    document.querySelectorAll('.accept-friend').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            acceptFriendRequest(userId, this);
        });
    });
    
    // Reject friend request functionality
    document.querySelectorAll('.reject-friend').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            rejectFriendRequest(userId, this);
        });
    });
}

/**
 * Send a friend request
 * 
 * @param {number} userId User ID
 * @param {HTMLElement} button Button element
 */
function sendFriendRequest(userId, button) {
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    button.disabled = true;
    
    // Send AJAX request to send friend request
    fetch('actions/send_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button appearance
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            button.disabled = true;
        } else {
            // Reset button
            button.innerHTML = '<i class="fas fa-user-plus"></i>';
            button.disabled = false;
            
            // Show error message
            alert(data.message || 'Une erreur est survenue. Veuillez réessayer.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = '<i class="fas fa-user-plus"></i>';
        button.disabled = false;
    });
}

/**
 * Accept a friend request
 * 
 * @param {number} userId User ID
 * @param {HTMLElement} button Button element
 */
function acceptFriendRequest(userId, button) {
    const requestItem = button.closest('.friend-request-item');
    
    // Send AJAX request to accept friend request
    fetch('actions/accept_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add fade-out animation
            requestItem.classList.add('fade-out');
            
            // Remove request item after animation
            setTimeout(function() {
                requestItem.remove();
                
                // Update friend request count
                updateFriendRequestCount();
            }, 300);
        } else {
            // Show error message
            alert(data.message || 'Une erreur est survenue. Veuillez réessayer.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Reject a friend request
 * 
 * @param {number} userId User ID
 * @param {HTMLElement} button Button element
 */
function rejectFriendRequest(userId, button) {
    const requestItem = button.closest('.friend-request-item');
    
    // Send AJAX request to reject friend request
    fetch('actions/reject_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add fade-out animation
            requestItem.classList.add('fade-out');
            
            // Remove request item after animation
            setTimeout(function() {
                requestItem.remove();
                
                // Update friend request count
                updateFriendRequestCount();
            }, 300);
        } else {
            // Show error message
            alert(data.message || 'Une erreur est survenue. Veuillez réessayer.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update friend request count
 */
function updateFriendRequestCount() {
    const requestItems = document.querySelectorAll('.friend-request-item');
    const countElement = document.getElementById('friend-request-count');
    
    if (countElement) {
        const count = requestItems.length;
        
        if (count === 0) {
            countElement.textContent = '';
            
            // Show no requests message
            const requestsList = document.querySelector('.friend-requests-list');
            if (requestsList) {
                requestsList.innerHTML = '<div class="text-center p-3">Aucune demande d\'ami en attente</div>';
            }
        } else {
            countElement.textContent = count;
        }
    }
}

/**
 * Initialize form validations
 */
function initializeFormValidations() {
    // Registration form validation
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            if (!validateRegistrationForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Post form validation
    const postForm = document.querySelector('form[action="actions/create_post.php"]');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            const contentInput = this.querySelector('textarea[name="content"]');
            if (contentInput.value.trim() === '') {
                e.preventDefault();
                alert('Veuillez écrire quelque chose avant de publier.');
            }
        });
    }
}

/**
 * Validate registration form
 * 
 * @return {boolean} True if form is valid, false otherwise
 */
function validateRegistrationForm() {
    const form = document.getElementById('registration-form');
    let isValid = true;
    
    // Validate name
    const nameInput = form.querySelector('input[name="name"]');
    if (nameInput.value.trim() === '') {
        showError(nameInput, 'Veuillez entrer votre nom');
        isValid = false;
    } else {
        hideError(nameInput);
    }
    
    // Validate email
    const emailInput = form.querySelector('input[name="email"]');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
        showError(emailInput, 'Veuillez entrer une adresse email valide');
        isValid = false;
    } else {
        hideError(emailInput);
    }
    
    // Validate password
    const passwordInput = form.querySelector('input[name="password"]');
    if (passwordInput.value.length < 6) {
        showError(passwordInput, 'Le mot de passe doit contenir au moins 6 caractères');
        isValid = false;
    } else {
        hideError(passwordInput);
    }
    
    // Validate password confirmation
    const confirmInput = form.querySelector('input[name="confirm_password"]');
    if (confirmInput.value !== passwordInput.value) {
        showError(confirmInput, 'Les mots de passe ne correspondent pas');
        isValid = false;
    } else {
        hideError(confirmInput);
    }
    
    return isValid;
}

/**
 * Show error message for an input
 * 
 * @param {HTMLElement} input Input element
 * @param {string} message Error message
 */
function showError(input, message) {
    input.classList.add('is-invalid');
    
    // Create or update error message
    let errorElement = input.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        input.parentNode.insertBefore(errorElement, input.nextSibling);
    }
    
    errorElement.textContent = message;
}

/**
 * Hide error message for an input
 * 
 * @param {HTMLElement} input Input element
 */
function hideError(input) {
    input.classList.remove('is-invalid');
    
    // Remove error message
    const errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains('invalid-feedback')) {
        errorElement.textContent = '';
    }
}

/**
 * Initialize image previews
 */
function initializeImagePreviews() {
    // Profile picture preview
    const profilePicInput = document.getElementById('profile-pic-input');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            previewImage(this, 'profile-pic-preview');
        });
    }
    
    // Cover picture preview
    const coverPicInput = document.getElementById('cover-pic-input');
    if (coverPicInput) {
        coverPicInput.addEventListener('change', function() {
            previewImage(this, 'cover-pic-preview');
        });
    }
    
    // Post image preview
    const postImageInput = document.getElementById('post-image-input');
    if (postImageInput) {
        postImageInput.addEventListener('change', function() {
            const previewContainer = document.getElementById('post-image-preview-container');
            previewImage(this, 'post-image-preview');
            previewContainer.classList.remove('d-none');
        });
    }
    
    // Add post image button
    const addPostImageBtn = document.getElementById('add-post-image');
    if (addPostImageBtn) {
        addPostImageBtn.addEventListener('click', function() {
            document.getElementById('post-image-input').click();
        });
    }
    
    // Remove post image button
    const removePostImageBtn = document.getElementById('remove-post-image');
    if (removePostImageBtn) {
        removePostImageBtn.addEventListener('click', function() {
            const postImageInput = document.getElementById('post-image-input');
            const previewContainer = document.getElementById('post-image-preview-container');
            
            // Clear the file input
            postImageInput.value = '';
            
            // Hide the preview
            previewContainer.classList.add('d-none');
        });
    }
}

/**
 * Preview an image
 * 
 * @param {HTMLElement} input File input element
 * @param {string} previewId ID of the preview element
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Initialize notifications
 */
function initializeNotifications() {
    // Mark notification as read
    document.querySelectorAll('.notification-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            markNotificationAsRead(notificationId);
        });
    });
    
    // Simulate real-time notifications (for demo purposes)
    simulateNotifications();
}

/**
 * Mark a notification as read
 * 
 * @param {number} notificationId Notification ID
 */
function markNotificationAsRead(notificationId) {
    // Send AJAX request to mark notification as read
    fetch('actions/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification appearance
            const notificationItem = document.querySelector('.notification-item[data-notification-id="' + notificationId + '"]');
            if (notificationItem) {
                notificationItem.classList.remove('unread');
            }
            
            // Update notification count
            updateNotificationCount();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update notification count
 */
function updateNotificationCount() {
    const unreadNotifications = document.querySelectorAll('.notification-item.unread');
    const countElement = document.querySelector('.nav-link .badge');
    
    if (countElement) {
        const count = unreadNotifications.length;
        
        if (count === 0) {
            countElement.style.display = 'none';
        } else {
            countElement.style.display = 'inline-block';
            countElement.textContent = count > 9 ? '9+' : count;
        }
    }
}

/**
 * Simulate real-time notifications (for demo purposes)
 */
function simulateNotifications() {
    // This is just for demo purposes to simulate real-time notifications
    // In a real application, you would use WebSockets or server-sent events
    
    const notificationTypes = [
        { type: 'like', message: 'a aimé votre publication', icon: 'fa-thumbs-up' },
        { type: 'comment', message: 'a commenté votre publication', icon: 'fa-comment' },
        { type: 'friend', message: 'a accepté votre demande d\'ami', icon: 'fa-user-friends' }
    ];
    
    // Simulate a notification every 30-60 seconds
    setInterval(function() {
        // Only simulate if user is logged in
        if (document.querySelector('.navbar-nav .dropdown-toggle')) {
            // Random chance to show notification (1 in 3)
            if (Math.random() < 0.3) {
                const randomType = notificationTypes[Math.floor(Math.random() * notificationTypes.length)];
                simulateNewNotification(randomType.type, 'John Doe ' + randomType.message, randomType.icon);
            }
        }
    }, 30000 + Math.random() * 30000);
}

/**
 * Simulate a new notification
 * 
 * @param {string} type Notification type
 * @param {string} message Notification message
 * @param {string} icon Notification icon
 */
function simulateNewNotification(type, message, icon) {
    // Update notification count
    const countElement = document.querySelector('.nav-link .badge');
    
    if (countElement) {
        let count = parseInt(countElement.textContent) || 0;
        count++;
        
        countElement.style.display = 'inline-block';
        countElement.textContent = count > 9 ? '9+' : count;
        
        // Show notification toast
        showNotificationToast(type, message, icon);
    }
}

/**
 * Show a notification toast
 * 
 * @param {string} type Notification type
 * @param {string} message Notification message
 * @param {string} icon Notification icon
 */
function showNotificationToast(type, message, icon) {
    // Create toast element
    const toastElement = document.createElement('div');
    toastElement.className = 'toast';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    
    toastElement.innerHTML = `
        <div class="toast-header">
            <i class="fas ${icon} text-primary me-2"></i>
            <strong class="me-auto">Nouvelle notification</strong>
            <small>À l'instant</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    // Add toast to container
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toastElement);
    
    // Initialize and show toast
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Remove toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Initialize message system
 */
function initializeMessageSystem() {
    // Send message functionality
    const messageForm = document.getElementById('message-form');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = this.querySelector('input[name="message"]');
            const conversationId = this.querySelector('input[name="conversation_id"]').value;
            
            if (messageInput.value.trim() !== '') {
                sendMessage(conversationId, messageInput.value);
                messageInput.value = '';
            }
        });
    }
    
    // Initialize message reactions
    initializeMessageReactions();
}

/**
 * Initialize message reaction functionality
 */
function initializeMessageReactions() {
    // Add event listeners to reaction buttons
    document.querySelectorAll('.reaction-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const messageId = this.closest('.message').getAttribute('data-message-id');
            const reactionType = this.getAttribute('data-reaction');
            addMessageReaction(messageId, reactionType, this);
        });
    });
}

/**
 * Add or update a reaction for a message
 * 
 * @param {number} messageId Message ID
 * @param {string} reactionType Reaction type (like, love, haha, wow, sad, angry)
 * @param {HTMLElement} button Reaction button element
 */
function addMessageReaction(messageId, reactionType, button) {
    // Send AJAX request to add reaction
    fetch('actions/add_message_reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message_id=' + messageId + '&reaction_type=' + reactionType
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Define reaction emojis
            const reactionEmojis = {
                'like': '👍',
                'love': '❤️',
                'haha': '😂',
                'wow': '😮',
                'sad': '😢',
                'angry': '😡'
            };
            
            // Update button appearance
            const allButtons = button.closest('.reaction-buttons').querySelectorAll('.reaction-btn');
            allButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            if (data.added || data.changed) {
                // Add active class to the clicked button
                button.classList.add('active');
            }
            
            // Update reaction summary
            updateMessageReactionSummary(messageId, data.counts);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Update the message reaction summary display
 * 
 * @param {number} messageId Message ID
 * @param {object} counts Reaction counts
 */
function updateMessageReactionSummary(messageId, counts) {
    const message = document.querySelector('.message[data-message-id="' + messageId + '"]');
    if (!message) return;
    
    const summaryContainer = message.querySelector('.reaction-summary');
    if (!summaryContainer) return;
    
    // Define reaction emojis
    const reactionEmojis = {
        'like': '👍',
        'love': '❤️',
        'haha': '😂',
        'wow': '😮',
        'sad': '😢',
        'angry': '😡'
    };
    
    if (counts.total > 0) {
        // Create or update reaction summary
        let html = '';
        
        // Display reaction icons (up to 3)
        let displayedReactions = 0;
        for (const type in reactionEmojis) {
            if (counts[type] > 0 && displayedReactions < 3) {
                displayedReactions++;
                html += `<span class="reaction-count">${reactionEmojis[type]} ${counts[type]}</span>`;
            }
        }
        
        summaryContainer.innerHTML = html;
    } else {
        // No reactions, clear the container
        summaryContainer.innerHTML = '';
    }
}

/**
 * Send a message
 * 
 * @param {number} conversationId Conversation ID
 * @param {string} message Message content
 */
function sendMessage(conversationId, message) {
    // Prevent sending empty messages
    if (message.trim() === '') {
        return;
    }
    
    // Send AJAX request to send message
    fetch('actions/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'conversation_id=' + conversationId + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add message to conversation
            addMessageToConversation(data.message);
            
            // Scroll to bottom of conversation
            scrollToBottomOfConversation();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Add a message to the conversation
 * 
 * @param {object} message Message object
 */
function addMessageToConversation(message) {
    const messageContainer = document.querySelector('.message-container');
    
    if (messageContainer) {
        const messageElement = document.createElement('div');
        const isSender = message.is_sender !== undefined ? message.is_sender : true;
        messageElement.className = isSender ? 'message sent' : 'message received';
        messageElement.setAttribute('data-message-id', message.id);
        
        // Only include content div if message is not empty
        const contentHtml = message.content && message.content.trim() ? 
            `<div class="message-content">${message.content.replace(/\n/g, '<br>')}</div>` : '';
        
        let timeHtml = `<div class="message-time">${message.time}`;
        
        // Only add read receipt for sent messages
        if (isSender) {
            timeHtml += `
                <span class="read-receipt ms-1">
                    <span class="read-receipt-unread"></span>
                </span>
            `;
        }
        
        timeHtml += `</div>`;
        
        messageElement.innerHTML = `
            ${contentHtml}
            ${timeHtml}
            <div class="message-reactions">
                <div class="reaction-summary"></div>
                <div class="reaction-buttons">
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="like" title="J'aime">👍</button>
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="love" title="J'adore">❤️</button>
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="haha" title="Haha">😂</button>
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="wow" title="Wow">😮</button>
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="sad" title="Triste">😢</button>
                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="angry" title="Grrr">😡</button>
                </div>
            </div>
        `;
        
        messageContainer.appendChild(messageElement);
        
        // Initialize reaction buttons for the new message
        const reactionButtons = messageElement.querySelectorAll('.reaction-btn');
        reactionButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const messageId = messageElement.getAttribute('data-message-id');
                const reactionType = this.getAttribute('data-reaction');
                addMessageReaction(messageId, reactionType, this);
            });
        });
    }
}

/**
 * Scroll to bottom of conversation
 */
function scrollToBottomOfConversation() {
    const messageContainer = document.querySelector('.message-container');
    
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}